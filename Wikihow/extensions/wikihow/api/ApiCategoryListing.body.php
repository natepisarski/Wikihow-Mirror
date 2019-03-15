<?php

class ApiCategoryListing extends ApiBase {
    public function __construct($main, $action) {
        parent::__construct($main, $action);
    }

    function execute() {
        // Get the parameters
        $params = $this->extractRequestParams();

        $result = $this->getResult();
        $module = $this->getModuleName();
        $error = '';
        $name = $params['name'];
        $title = null;

        if ($name) {
            $title = Title::newFromText($name, NS_CATEGORY);
            if (!$title || !$title->exists()) {
                $error = 'Title not found';
            }
        }
        $resultProps = CategoryLister::getCategoryContents($this->getContext(), $title);

        $result->addValue(null, $module, $resultProps);

        if ($error) {
            $result->addValue(null, $module, array('error' => $error));
        }

        return true;
    }

    public function getResultProperties() {
        return array(
            'categorylisting' => array(
                'subcats' => array(
                    ApiBase::PROP_TYPE => array('string' => 'string')),
                'f_articles' => array(
                    ApiBse::PROP_TYPE => array('string' => 'string')),
                'articles' => array(
                    ApiBse::PROP_TYPE => array('string' => 'string')),
            ),
        );
    }

    public function getAllowedParams() {
        return array(
            'name' => array(
                ApiBase::PARAM_TYPE => 'string',
            ),
        );
    }

    public function getParamDescription() {
        return array(
            'name' => 'Category title for which to fetch contents '
                    . '(assumes top level if none given)',
        );
    }

    public function getDescription() {
        return 'An API extension to get the contents of wikiHow categories';
    }

    public function getPossibleErrors() {
        return parent::getPossibleErrors();
    }

    public function getExamples() {
        return array(
            'api.php?action=categorylisting&name=Arts-and-Entertainment'
        );
    }

    public function getHelpUrls() {
        return '';
    }

    public function getVersion() {
        return '0.0.1';
    }
}

class CategoryLister {
    public static function getCategoryContents($context, $title=null) {
        $cats = array();
        $fArts = array();
        $arts = array();

        if (!$title) {
            $cats = CategoryHelper::getTopLevelCategoriesForDropDown();
        } else {
            $tree = CategoryHelper::getCategoryTreeArray();
            self::getChildNodesFromTreeNode($tree, str_replace('-', ' ', $title->getBaseText()), $cats);
        }

        $catResult = array();

        foreach ($cats as $cat) {
            $catTitle = Title::newFromText($cat, NS_CATEGORY);
            if ($catTitle) {
	            $catResult[$cat] = $catTitle->getFullURL();
            }
        }

        $fArtResult = array();
        $artResult = array();

        if ($title && $title->exists()) {
            $viewer = new WikihowCategoryViewer($title, $context);
            $viewer->clearState();
            $viewer->doQuery();

            // Featured articles:
            if ($viewer->articles_fa) {
                foreach ($viewer->articles_fa as $fArtTitle) {
                    if ($fArtTitle && $fArtTitle->exists()
                            && $fArtTitle->getNamespace() === NS_MAIN) {
                        $fArtResult[$fArtTitle->getText()] = $fArtTitle->getFullURL();
                    }
                }
            }

            // General articles:
            if ($viewer->articles) {
                foreach ($viewer->articles as $artTitle) {
                    if ($artTitle && $artTitle->exists()
                            && $artTitle->getNamespace() === NS_MAIN) {
                        $artResult[$artTitle->getText()] = $artTitle->getFullURL();
                    }
                }
            }
        }

        return array('subcats' => $catResult,
                     'f_articles' => $fArtResult,
                     'articles' => $artResult);
    }

    public static function getChildNodesFromTreeNode($tree, $parent, &$result=array()) {
        if (array_key_exists($parent, $tree)) {
            if (is_array($tree[$parent])) {
                $tmpChildren = array_keys($tree[$parent]);
                foreach ($tmpChildren as $child) {
                    if (!in_array($child, $result) && $child != $parent) {
                        $result[] = $child;
                    }
                }
            }
        } else {
            foreach ($tree as $node => $subtree) {
                if (is_array($subtree) && !empty($subtree)) {
                    self::getChildNodesFromTreeNode($subtree, $parent, $result);
                }
            }
        }
    }
}
