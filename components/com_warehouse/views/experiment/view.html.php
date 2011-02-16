<?php 

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

require_once 'libraries/joomla/application/module/helper.php';
require_once 'lib/data/MaterialPeer.php';
require_once 'lib/security/Authorizer.php';

class WarehouseViewExperiment extends JView{
	
  function display($tpl = null){
    //get the tabs to display on the page
    /* @var $oExperimentModel WarehouseModelExperiment */
    $oExperimentModel =& $this->getModel();

    $iExperimentId = JRequest::getVar("id");
    $oExperiment = ExperimentPeer::retrieveByPK($iExperimentId);
    //$strDisplayDescription = $oExperimentModel->getDisplayDescription($oExperiment->getDescription());
    //$oExperiment->setDescription($strDisplayDescription);
    $_REQUEST[Experiments::SELECTED] = serialize($oExperiment);
    $this->assignRef( "strDates", $this->getDates($oExperiment) );

    $iProjectId = JRequest::getVar('projid');
    $this->assignRef( "projid", $iProjectId );

    $oProject = ProjectPeer::find($iProjectId);
    //$_REQUEST[Search::SELECTED] = serialize($oProject);

    $strTabArray = $oExperimentModel->getTabArray();
    $strTabViewArray = $oExperimentModel->getTabViewArray();
    $strTabHtml = $oExperimentModel->getTabs( "warehouse", $iProjectId, $strTabArray, $strTabViewArray, "experiments" );
    $this->assignRef( "strTabs", $strTabHtml );

    //20100529 - display the interactive files above data
    $oToolFileArray = $oExperimentModel->findDataFileByTool("inDEED", $iProjectId, $oExperiment->getId());
    $_REQUEST["TOOL_DATA_FILES"] = serialize($oToolFileArray);

    $oTrialArray = $oExperimentModel->findTrialsByExperiment($oExperiment->getId(), "trialid");
    $_REQUEST[TrialPeer::TABLE_NAME] = serialize($oTrialArray);

    //if there's only 1 trial, get the reps
    $oRepetitionArray = array();
    if(sizeof($oTrialArray)==1){
      $oRepetitionArray = $oExperimentModel->findRepititionsByTrial($oTrialArray[0]->getId());
    }

    //if there's only 1 repetition, get the data
    $oDataFileArray = array();
    if(sizeof($oRepetitionArray)==1){
      $strPath = $oRepetitionArray[0]->getPathname();
      $this->assignRef( "strCurrentPath", $strPath );
      
      $oDataFileArray = DataFilePeer::findByDirectory($strPath);

      $strPathArray = explode("/", $strPath);
      $strBackArray = array_diff($strPathArray, array(array_pop($strPathArray)));
      $strBackPath = implode("/", $strBackArray);
      $this->assignRef( "strBackPath", $strBackPath );
    }
    $_REQUEST["ExperimentDataFiles"] = serialize($oDataFileArray);


    $_REQUEST[OrganizationPeer::TABLE_NAME] = serialize($oExperimentModel->findFacilityByExperiment($iExperimentId));

    $oSpecimen = $oExperimentModel->findSpecimenByProject($iProjectId);
    $_REQUEST[SpecimenPeer::TABLE_NAME] = serialize($oSpecimen);

    $oDrawingArray = $oExperimentModel->findDataFileByEntityType("Drawing", $iProjectId, $iExperimentId);
    $oDrawingArray = $oExperimentModel->resizePhotos($oDrawingArray);

    //print_r($oDrawingArray);
    $_REQUEST["Drawings"] = serialize($oDrawingArray);
    //$_SESSION["Drawings"] = serialize(array());

    /*
     * Check to see if the current experiment has any repetitions.
     * If yes, display repetitions in the table.  If no, hide repetitions.
     */
    $oDataFileLinkArray = $oExperimentModel->findRepetitionDataFileLinksByExperiment($oExperiment->getId());
    $this->assignRef( "repetitionDataFileSize", sizeof($oDataFileLinkArray) );

    $oLocationPlanArray = $oExperimentModel->findLocationPlansByExperiment($oExperiment->getId());
    $_REQUEST[LocationPlanPeer::TABLE_NAME] = serialize($oLocationPlanArray);

    $oMaterialArray = $oExperimentModel->findMaterialsByExperiment($oExperiment->getId());
    $_REQUEST[MaterialPeer::TABLE_NAME] = serialize($oMaterialArray);

    $iPhotoFileCount = $oExperimentModel->findDataFileByMimeTypeCount($iProjectId, $oExperiment->getId());
    $this->assignRef( "photoCount", $iPhotoFileCount );

    // update and get the page views
    $iEntityViews = $oExperimentModel->getPageViews(3, $oExperiment->getId());
    $this->assignRef("iEntityActivityLogViews", $iEntityViews);

    // update and get the page views
    $iEntityDownloads = $oExperimentModel->getEntityDownloads(3, $oExperiment->getId());
    $this->assignRef("iEntityActivityLogDownloads", $iEntityDownloads);

    /* @var $oHubUser JUser */
    $oHubUser = $oExperimentModel->getCurrentUser();
    $this->assignRef( "strUsername", $oHubUser->username );

    $strReturnURL = $oExperimentModel->getReturnURL();
    $this->assignRef( "warehouseURL", $strReturnURL );

    $iDocumentFileCount = count($oExperimentModel->findDataFileByDirPath($oExperiment->getPathname()."/Documentation"));
    $this->assignRef( "iDocumentCount", $iDocumentFileCount );

    $iAnalysisFileCount = count($oExperimentModel->findDataFileByDirPath($oExperiment->getPathname()."/Analysis"));
    $this->assignRef( "iAnalysisCount", $iAnalysisFileCount );

    $this->assignRef( "mod_warehousedocs", ComponentHtml::getModule("mod_warehousedocs") );
    $this->assignRef( "mod_warehousetags", ComponentHtml::getModule("mod_warehousetags") );
    $this->assignRef( "mod_curationprogress", ComponentHtml::getModule("mod_curationprogress") );
    $this->assignRef( "mod_warehousefilmstrip", ComponentHtml::getModule("mod_warehousefilmstrip") );

    $bSearch = false;
    if(isset($_SESSION[Search::KEYWORDS])){
      if(StringHelper::hasText($_SESSION[Search::KEYWORDS])){
        $bSearch = true;
      }
    }
    if(isset($_SESSION[Search::FUNDING_TYPE])){
      if(StringHelper::hasText($_SESSION[Search::FUNDING_TYPE])){
        $bSearch = true;
      }
    }
    if(isset($_SESSION[Search::MEMBER])){
      if(StringHelper::hasText($_SESSION[Search::MEMBER])){
        $bSearch = true;
      }
    }
    if(isset($_SESSION[Search::NEES_SITE])){
      if($_SESSION[Search::NEES_SITE]){
        $bSearch = true;
      }
    }
    if(isset($_SESSION[Search::PROJECT_TYPE])){
      if($_SESSION[Search::PROJECT_TYPE]){
        $bSearch = true;
      }
    }
    if(isset($_SESSION[Search::PROJECT_IDS])){
      if(StringHelper::hasText($_SESSION[Search::PROJECT_IDS])){
        $bSearch = true;
      }
    }
    if(isset($_SESSION[Search::AWARDS])){
      if(StringHelper::hasText($_SESSION[Search::AWARDS])){
        $bSearch = true;
      }
    }
    if(isset($_SESSION[Search::MATERIAL_TYPES])){
      if(StringHelper::hasText(Search::MATERIAL_TYPES)){
        $bSearch = true;
      }
    }
    if(isset($_SESSION[Search::PROJECT_YEAR])){
      if($_SESSION[Search::PROJECT_YEAR]){
        $bSearch = true;
      }
    }
    //if(isset($_SESSION[Search::START_DATE]))$bSearch = true;
    //if(isset($_SESSION[Search::END_DATE]))$bSearch = true;

    //set the breadcrumbs
    JFactory::getApplication()->getPathway()->addItem("Project Warehouse","/warehouse");
    if($bSearch){
      JFactory::getApplication()->getPathway()->addItem("Results","/warehouse/find?keywords=".$_SESSION[Search::KEYWORDS]
                                                                                            . "&funding=".$_SESSION[Search::FUNDING_TYPE]
                                                                                            . "&member=".$_SESSION[Search::MEMBER]
                                                                                            . "&neesSite=".$_SESSION[Search::NEES_SITE]
                                                                                            . "&projectType=".$_SESSION[Search::PROJECT_TYPE]
                                                                                            . "&projid=".$_SESSION[Search::PROJECT_IDS]
                                                                                            . "&award=".$_SESSION[Search::AWARDS]
                                                                                            . "&materialType=".$_SESSION[Search::MATERIAL_TYPES]
                                                                                            . "&projectYear=".$_SESSION[Search::PROJECT_YEAR]);
//                                                                                            . "&startdate=".$_SESSION[Search::START_DATE]
//                                                                                            . "&startdate=".$_SESSION[Search::END_DATE]);
    }
    JFactory::getApplication()->getPathway()->addItem($oProject->getName(),"/warehouse/project/$iProjectId");
    JFactory::getApplication()->getPathway()->addItem("Experiments","/warehouse/experiments/$iProjectId");
    JFactory::getApplication()->getPathway()->addItem($oExperiment->getName(),"javascript:void(0)");
    parent::display($tpl);
  }
  
  /**
   * Gets the list of organizations for the project
   * @return array of organization names
   */
  private function getOrganizations($p_oExperiment){
    return OrganizationPeer::findByExperiment($p_oExperiment->getId());
  }
  
  /**
   * Gets the project dates
   * @return start and end date
   */
  private function getDates($p_oExperiment){
  	//if no start date, return empty string
    $strDates = trim($p_oExperiment->getStartDate()); 
    if(strlen($strDates) == 0){
      return $strDates;
    }
    
    //if we have start but no end date, enter Present
    if(strlen($p_oExperiment->getEndDate())>0){
      $strDates = strftime("%B %d, %Y", strtotime($strDates)) . " - ". strftime("%B %d, %Y", strtotime($p_oExperiment->getEndDate()));
      //$strDates = $strDates . " to ". $p_oExperiment->getEndDate();
    }else{
      //$strDates = $strDates . " to Present";
      $strDates = strftime("%B %d, %Y", strtotime($strDates)) . " to Present";
    }
    return $strDates;
  }
}

?>
