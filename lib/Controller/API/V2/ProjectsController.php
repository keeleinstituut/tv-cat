<?php

namespace API\V2;

use API\V2\Json\Project;
use API\V2\Json\ProjectAnonymous;
use API\V2\Validators\ProjectPasswordValidator;
use Constants_Engines;
use Engine;
use Engines_MTee;
use Engines_NecTM;
use Exception;
use Jobs_JobDao;
use Projects_ProjectDao;
use TMKeysUtils;
use Translations_SegmentTranslationDao;
use Utils;

/**
 * This controller can be called as Anonymous, but only if you already know the id and the password
 *
 * Class ProjectsController
 * @package API\V2
 */
class ProjectsController extends KleinController
{

    /**
     * @var \Projects_ProjectStruct
     */
    private $project;

    /**
     * @var ProjectPasswordValidator
     */
    private $projectValidator;

    public function get()
    {

        if (empty($this->user)) {
            $formatted = new ProjectAnonymous();
        } else {
            $formatted = new Project();
            $formatted->setUser($this->user);
            if (!empty($this->api_key)) {
                $formatted->setCalledFromApi(true);
            }
        }

        $this->featureSet->loadForProject($this->project);
        $projectOutputFields = $formatted->renderItem($this->project);
        $this->response->json(['project' => $projectOutputFields]);

    }

    public function setDueDate()
    {
        $this->updateDueDate();
    }

    public function updateDueDate()
    {
        if (
            array_key_exists("due_date", $this->params)
            &&
            is_numeric($this->params['due_date'])
            &&
            $this->params['due_date'] > time()
        ) {

            $due_date = \Utils::mysqlTimestamp($this->params['due_date']);
            $project_dao = new Projects_ProjectDao;
            $project_dao->updateField($this->project, "due_date", $due_date);
        }
        if (empty($this->user)) {
            $formatted = new ProjectAnonymous();
        } else {
            $formatted = new Project();
        }

        //$this->response->json( $this->project->toArray() );
        $this->response->json(['project' => $formatted->renderItem($this->project)]);
    }

    public function deleteDueDate()
    {
        $project_dao = new Projects_ProjectDao;
        $project_dao->updateField($this->project, "due_date", null);

        if (empty($this->user)) {
            $formatted = new ProjectAnonymous();
        } else {
            $formatted = new Project();
        }
        $this->response->json(['project' => $formatted->renderItem($this->project)]);
    }

    public function toggleMTEnabled()
    {
        $project = $this->project;
        $enableMT = boolval($this->getPutParams()['enabled'] ?? true);

        if ($enableMT === $project->isMTEnabled()) {
            return $this->response->json([]);
        }

        if ($enableMT) {
            /**
             * MTee is not used since 01.10.2024
             */
            //Jobs_JobDao::updateAllJobsMTByProjectId($project->id, Engines_MTee::getMTeeID());
            return $this->response->json([]);
        }

        Jobs_JobDao::updateAllJobsMTByProjectId($project->id, Constants_Engines::NO_MT_ENGINE_ID);
        return $this->response->json([]);
    }

    public function cancel()
    {
        return $this->changeStatus(\Constants_JobStatus::STATUS_CANCELLED);
    }

    public function archive()
    {
        return $this->changeStatus(\Constants_JobStatus::STATUS_ARCHIVED);
    }

    public function active()
    {
        return $this->changeStatus(\Constants_JobStatus::STATUS_ACTIVE);
    }

    protected function changeStatus($status)
    {

        $chunks = $this->project->getJobs();

        foreach ($chunks as $chunk) {

            // update a job only if it is NOT deleted
            if (!$chunk->wasDeleted()) {
                Jobs_JobDao::updateJobStatus($chunk, $status);

                $lastSegmentsList = Translations_SegmentTranslationDao::getMaxSegmentIdsFromJob($chunk);
                Translations_SegmentTranslationDao::updateLastTranslationDateByIdList($lastSegmentsList, Utils::mysqlTimestamp(time()));
            }
        }

        $this->response->json(['code' => 1, 'data' => "OK", 'status' => $status]);

    }

    /**
     * @return void
     * @throws Exception
     */
    public function setTMKeys()
    {
        $project = $this->project;
        $newKeys = TMKeysUtils::parse($this->getPutParams()['tm_keys'] ?? '');

        if (empty($newKeys)) {
            $this->response->json(['data' => []]);
            return;
        }

        /** @var Engines_NecTM $engine */
        $engine = Engine::getInstance(Engines_NecTM::getID());

        $errors = $engine->validateTmKeys($newKeys);
        if (!empty($errors)) {
            $this->response->code(422);
            $this->response->json(['errors' => $errors]);
            return;
        }

        foreach ($project->getJobs() as $job) {
            Jobs_JobDao::updateJobTMKeys($job, $newKeys);
        }

        $this->response->json(['data' => $newKeys]);
    }

    protected function afterConstruct()
    {

        $projectValidator = (new ProjectPasswordValidator($this));

        $projectValidator->onSuccess(function () use ($projectValidator) {
            $this->project = $projectValidator->getProject();
        });

        $this->appendValidator($projectValidator);
    }

}