<?php
namespace app\models;

use app\libraries\Core;
use app\libraries\DatabaseUtils;
use app\libraries\FileUtils;
use app\libraries\GradeableType;

class RainbowCustomization extends AbstractModel{
    /**/
    protected $core;
    private $customization_data;
    private $has_error;
    private $error_messages;
    private $used_buckets;
    private $available_buckets;

    /*XXX: This is duplicated from AdminGradeableController.php, we really shouldn't have multiple copies lying around.
     * On top of that, Rainbow Grades has its own enum internally. Since that's a separate repo it's probably
     * unavoidable, but the fewer places we can duplicate this, the better.
     */
    /*XXX: It's also going to be annoying to have "none (for practice only)" since in customization it's "none"
     * which is a lot nicer to type.
     */
    //XXX: 'none (for practice only)' we want to truncate to just 'none'.
    const syllabus_buckets = [
        'homework', 'assignment', 'problem-set',
        'quiz', 'test', 'exam',
        'exercise', 'lecture-exercise', 'reading', 'lab', 'recitation', 'worksheet',
        'project',
        'participation', 'note',
        'none'];


    public function __construct(Core $main_core) {
        $this->core = $main_core;
        $this->has_error = "false";
        $this->error_messages = [];
    }

    public function buildCustomization(){
        //This function should examine the DB(?) / a file(?) and if customization settings already exist, use them. Otherwise, populate with defaults.
        $this->customization_data = [];

        //$gids = $this->core->getQueries()->getAllGradeablesIdsAndTitles();
        $gradeables = $this->core->getQueries()->getAllGradeables();
        foreach ($gradeables as $gradeable){
            $bucket = $gradeable->getBucket();
            if(!isset($this->customization_data[$bucket])){
                $this->customization_data[$bucket] = [];
            }
            /*XXX: Right now we aren't yet worried about pulling in from existing customization.json but if we do, then what happens in the event of a conflict?
             * I'm tempted to say for version 1.0, too bad, we override with the version from the DB (since that's more up to date), if the gradeable exists
             * In a later version we may want to highlight it in red or something, and ask the user which number to use? I'm not sure if there's a use case for using
             * the version in the customization.json, but the warning might be nice. Might even be an error state where action is required, just so the user isn't
             * confused when the grade distribution shifts around.
             */
            $this->customization_data[$bucket][] = [
                "id" => $gradeable->getId(),
                "title" => $gradeable->getName(),
                "max_score" => $gradeable->getTotalAutograderNonExtraCreditPoints()+$gradeable->getTotalTAPoints()
            ];
        }

        //XXX: Assuming that the contents of these buckets will be lowercase
        $this->used_buckets = [];

        /*TODO: Read in used_buckets according to the customization.json if it exists and remove those from the
         * available_buckets array.
         */
        $this->available_buckets = array_diff(self::syllabus_buckets,$this->used_buckets);
    }

    public function getCustomizationData(){
        return $this->customization_data;
    }

    public function getAvailableBuckets(){
        return $this->available_buckets;
    }

    public function getUsedBuckets(){
        return $this->used_buckets;
    }

    public function getCustomizationJSON(){
        //Logic to trim down the customization data to just what's shown
        $json_data = [];
        return json_encode($json_data);
    }

    public function processForm(){
        $this->has_error = "true";
        foreach($_POST as $field => $value){
            $this->error_messages[] = "$field: $value";
        }
    }

    public function error(){
        return $this->has_error;
    }

    public function getErrorMessages(){
        return $this->error_messages;
    }
}