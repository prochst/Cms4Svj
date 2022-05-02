<?php

declare(strict_types=1);

namespace App\AdmModule\Presenters;

use Nette;
use App\Model\DataManager;
use App\Model\DataContext;
use App\Model\TableManager;
use App\Model\RoleManager;
use Ramsey\Uuid\Uuid;


final class AdmPresenter extends BaseAdmPresenter
{
    /** @var DataManager */
    private $dataManager;
    

    /** @var TableManager */
    private $tableMng;

    /**
     * @param DataManager $articleManager
     */
    public function __construct(DataManager $dataManager ) {
        parent::__construct();
        $this->tableMng = new TableManager($dataManager);
    }     

    public function renderDefault(): void {
        // místo pro dasboard
    } 
    
}
