<?php
global $IP;
require_once("$IP/extensions/wikihow/common/aws-sdk-php-v3/aws-autoloader.php");
/**
 * TQualManager.php
 * This module provides functionality to
 * manage turk workers.
 * - Assign or revoke qualifications
 * - Send messages to workers
 *
 * @author rjsbhatia
 */

/**
 * Class TQualManager
 * specialpage only for staff
 */
class TQualManager extends UnlistedSpecialPage
{
    private $_mturk = NULL;
    private $_workersQualList = [];
    private $_allQualNames = [];

    /**
     * Class constructor
      * @return nothing
     */
    function __construct()
    {
        $this->action = $GLOBALS['wgTitle']->getPartialUrl();
        parent::__construct($this->action);
        $GLOBALS['wgHooks']['ShowSideBar'][] = ['TQualManager::removeSideBarCallback'];
    }

    static function removeSideBarCallback(&$showSideBar)
    {
        $showSideBar = false;
        return true;
    }

    // method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus()
	{
		return true;
	}

	/**
     * @param qualid qualification id
     * @return workerids array of workerids which have the qualid
     */
    function getWorkerIDsforQual($qualid)
    {
        $workerids = [];
        $nextToken = Null;
        do {
            if ($nextToken) {
                $workers = $this->_mturk->listWorkersWithQualificationType(
                    [
                    'QualificationTypeId' => $qualid,
                    'MaxResults' => 100,
                    'NextToken' => $nextToken
                    ]
                );
            } else {
                $workers = $this->_mturk->listWorkersWithQualificationType(
                    [
                    'QualificationTypeId' => $qualid,
                    'MaxResults' => 100
                    ]
                );
            }

            $nextToken = $workers['NextToken'];
            foreach ($workers['Qualifications'] as $key=>$worker) {
                $workerids[] = $worker['WorkerId'];
            }

        } while (!is_null($nextToken));

        return $workerids;
    }

    /**
     * @param qname qualification name
     * @return qualid the id for qualname
     */
    function getQualIdFromQualName($qname)
    {
        $qualId = array_search($qname, $this->_allQualNames);
        if ($qualId) {
            return $qualId;
        }
        // unexpected error, as the qualnames are being retreived
        // from checkboxes labels
        // which were dynamically generated
        echo("Qualification name does not match a
                qualification id, cannot update information.");
        exit;
    }

    /**
     * assign qualification to a worker
     * @param workerid amazon turk workerid
     * @param qualid the qual to be assigned to the worker
     */
    function assignQual($workerId, $qualId)
    {
        // the result is always empty
        $this->_mturk->associateQualificationWithWorker([
            'QualificationTypeId' => $qualId, // REQUIRED
            'SendNotification' => true,
            'WorkerId' => $workerId, // REQUIRED
        ]);
    }

    /**
     * remove qualification previously assigned to a worker
     * @param workerid amazon turk workerid
     * @param qualid the qual to be assigned to the worker
     */
    function removeQual($workerId, $qualId)
    {
        // the result is always empty
        $this->_mturk->disassociateQualificationFromWorker([
            'QualificationTypeId' => $qualId, // REQUIRED
            'WorkerId' => $workerId, // REQUIRED
        ]);
    }

    /**
     * Save changed made by user to amazon turk
     * @param workerid amazon turk workerid
     * @param qualChanges list of changes recoreded by the user
     * @return result update status meesage
     */
    function saveToAmzTurk($workerId, $qualChanges)
    {
        $log = [];
        $applicableQuals = $this->_workersQualList[$workerId];
        $qualChanges = json_decode($qualChanges);

        foreach ($qualChanges as $nqual=>$val) {
            $qualId = $this->getQualIdFromQualName(($nqual));

            if (!empty($applicableQuals) && in_array($nqual, $applicableQuals)) {
                // previous qual, if $val false then to remove
                // if value true, then do nothing
                if ($val) {
                    $log[] = "For worker " .$workerId .
                            " existing qualification detected - no change detected:"
                            .$nqual;
                } else {
                    $this->removeQual($workerId, $qualId);
                    $log[] = "For worker ".$workerId .
                            " qualification removed: "
                            .$nqual;
                }
            } else {
                // new qual, add
                if ($val) {
                    $this->assignQual($workerId, $qualId);
                    $log[] = "New qualification assigned to " .$workerId .
                            " qualification granted: "
                             .$nqual;
                } else {
                    $log[] = "New qualification not being assigned to "
                            .$workerId ." qualification not granted: "
                            .$nqual;
                }
            }
        }
        return $log;
    }

    /**
     * send notification to a worker using amazon turk system
     * @param workerid amazon turk workerid
     * @param message the qual to be assigned to the worker
     * @return result the return object from amazon
     */
    function sendMessage($workerid, $message)
    {
        // connect to turk, if not connected
        if (is_null($this->_mturk)) {
            $this->connectTurk();
        }

        $reply = $this->_mturk->notifyWorkers(
            [
            'MessageText' => $message, // REQUIRED
            'Subject' => 'Content Research Mechanical Turk Message', // REQUIRED
            'WorkerIds' => [$workerid] // REQUIRED
            ]
        );
        if ($reply[0]) {
            return $reply[0];
        }
        $reply = "Message delivered";
        return $reply;
    }

    /**
     * Get list of qualification for a worker
     * @param workerid amazon turk workerid
     * @return finalQuals all the qualifications for the workeride
     */
    function getQualList($workerId)
    {
        $finalQuals = [];
        $applicableQuals = [];
        $applicableQuals = $this->_workersQualList[$workerId];

        foreach ($this->_allQualNames as $qual) {
            if (!empty($applicableQuals) && in_array($qual, $applicableQuals)) {
                $finalQuals[$qual] = True;
            } else {
                $finalQuals[$qual] = False;
            }
        }
        return $finalQuals;
    }

    /**
     * LoadTurkData gets qualification data
     * from turk api
     * populates private vars
     * _allQualNames
     * _workersQualList
     * @return nothing
     */
    function loadTurkData()
    {
        // connect to turk, if not connected
        if (is_null($this->_mturk)) {
            $this->connectTurk();
        }

        $qualList = $this->_mturk->listQualificationTypes(
            [
            'MustBeOwnedByCaller' => true,
            'MustBeRequestable' => true
            ]
        );

        $qualList = $qualList['QualificationTypes'];

        foreach ($qualList as $value) {
            $this->_allQualNames[$value['QualificationTypeId']] = $value['Name'];
            $workerids = $this->getWorkerIDsforQual($value['QualificationTypeId']);

            foreach ($workerids as $workerid) {
                if (!isset($this->_workersQualList[$workerid])) {
                    $this->_workersQualList[$workerid] =
                        [$value['QualificationTypeId']=>$value['Name']];
                } else {
                    $this->_workersQualList[$workerid] +=
                        [$value['QualificationTypeId']=>$value['Name']];
                }
            }
        }
    }

    /**
     * This opens a connection to turk
     * and gets qualifications data
     * object saved in _mturk
     * @return nothing
     */
    function connectTurk()
    {
        //amazon turk keys
        $access_id = WH_AWS_AUTOTURK_ACCESS_KEY;
        $key = WH_AWS_AUTOTURK_SECRET_KEY;

        $this->_mturk = new Aws\MTurk\MTurkClient(
            [
            'credentials' =>
                [
                    'key'    => $access_id,
                    'secret' => $key
                ],
                'version' => 'latest',
                'region'  => 'us-east-1'
            ]
        );
    }

    /**
     * Execute special page.  Only available to wikihow staff.
     * @return nothing
     */
    function execute($par)
    {
        $req = $this->getRequest();
        $out = $this->getOutput();
        $user = $this->getUser();
        $userName = $user->getName();

        // Check permissions
        $userGroups = $user->getGroups();

        if ( ($userName != 'Rjsbhatia') && ( $user->isBlocked()
            || !(in_array('staff', $userGroups))) ) {
            $out->setRobotPolicy('noindex,nofollow');
            $out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
            return;
        }

        $out->addModules(['ext.wikihow.TQualManager']);
        $out->setHTMLTitle('Manage Turk worker qualifications/notfiy workers');
        $out->setPageTitle('Manage Turk Workers');

        $options = ['loader' => new Mustache_Loader_FilesystemLoader(__DIR__), ];
        $m = new Mustache_Engine($options);
        $tmpl = $m->render('tqualmanager.mustache');

        // get turk qual data
        $this->loadTurkData();

        // resolve ajax calls
        $action = $req->getVal('action');

        if ($action == 'get_qual_list') {
            $workerId = $req->getVal('worker_id');
            $qualList = $this->getQualList( $workerId );
            echo json_encode($qualList);
            exit;
        } elseif($action == 'save_quals') {
            $workerId = $req->getVal('worker_id');
            $changes = $req->getVal('qual_changes');
            $result = $this->saveToAmzTurk($workerId, $changes);
            echo json_encode($result);
            exit;
        } elseif($action == 'send_message') {
            $workerId = $req->getVal('worker_id');
            $message = $req->getVal('email_message');
            $result = $this->sendMessage($workerId, $message);
            echo json_encode($result);
            exit;
        }

        $out->addHTML( $tmpl );
    }
}
