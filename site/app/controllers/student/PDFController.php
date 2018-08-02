<?php

namespace app\controllers\student;


use app\controllers\AbstractController;
use app\libraries\FileUtils;

class PDFController extends AbstractController {
    public function run() {
        switch ($_REQUEST['action']) {
            case 'view_annotated_pdf':
                return $this->showAnnotatedPdf();
                break;
        }
    }

    private function showAnnotatedPdf(){
        $this->core->getOutput()->useFooter(false);
        $this->core->getOutput()->useHeader(false);
        $gradeable_id = $_GET['gradeable_id'] ?? NULL;
        $user_id = $this->core->getUser()->getId();
        $filename = $_GET['file_name'] ?? NULL;
        $active_version = $this->core->getQueries()->getGradeable($gradeable_id, $user_id)->getActiveVersion();
        $annotation_path = FileUtils::joinPaths($this->core->getConfig()->getCoursePath(), 'annotations', $gradeable_id, $user_id, $active_version);
        $first_file = scandir($annotation_path)[2];
        $annotation_path = FileUtils::joinPaths($annotation_path, $first_file);
        $annotation_jsons = [];
        $pdf_url = $this->core->buildUrl(array('component' => 'misc', 'page' => 'encodePDF'));
        if(is_dir($annotation_path)){
            $first_file = scandir($annotation_path)[2];
            $annotation_path = FileUtils::joinPaths($annotation_path, $first_file);
            if(is_file($annotation_path)) {
                $dir_iter = new \DirectoryIterator(dirname($annotation_path . '/'));
                foreach ($dir_iter as $fileinfo) {
                    if (!$fileinfo->isDot()) {
                        $grader_id = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileinfo->getFilename());
                        $grader_id = explode('_', $grader_id)[1];
                        $annotation_jsons[$grader_id] = file_get_contents($fileinfo->getPathname());
                    }
                }
            }
        }

        return $this->core->getOutput()->renderTwigOutput('grading/electronic/PDFAnnotationEmbedded.twig', [
            'gradeable_id' => $gradeable_id,
            'user_id' => $user_id,
            'filename' => $filename,
            'annotation_jsons' => json_encode($annotation_jsons),
            'student_popup' => true,
            'pdf_url_base' => $pdf_url
        ]);
    }
}
