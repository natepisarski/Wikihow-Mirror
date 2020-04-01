<?
$txt = "
dont
exe
alot
thats
ect
jewelery
doesnt
htis
puclish
mroeim
Zealand
goto
Homeschooli
theres
ve
doesn
thru
th
untill
su
accesories
cha
ka
thier
fave
gf
isnt
pk
hes
bs
ness
clich
eachother
somthing
excercise
der
clubpenguin
youre
buttercream
bc
remeber
ne
aren
ain't
til
mis
ogg
embarassing
throughly
arent
lil
reccomend
dosen't
esque
somethings
youself
didnt
tun
becuase
carefull
barbeque
wether
deoderant
doneness
ot
iis
sweetcorn
rubberband
placemat
CCleaner
recomended
highschool
noticable
esp
waisted
reccomended
placemats
hairbands
recomend
lightbulb
cornflour
togethers
spacebar
elses
preferrably
completly
burette
soo
hime
runtime
tre
foward
furni
get's
nee
hippy
smilies
hotdog
headbanging
";

$misspelled = explode("\n", trim($txt));

$sql = "select sa_page_id, page_title from spellcheck_articles, page where page_id = sa_page_id and (";

foreach ($misspelled as $word) {
	$word = mysql_real_escape_string($word);
	$clause[] = "sa_misspellings like '%,$word,%' OR sa_misspellings like '$word%' or sa_misspellings = '$word' ";
}

$sql .= implode(" OR ", $clause);
$sql .= ")";

echo $sql;
