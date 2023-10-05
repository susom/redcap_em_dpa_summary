<?php
namespace Stanford\DpaSummary;

include_once 'emLoggerTrait.php';

use ExternalModules\ExternalModules;
use Project;
use REDCap;
class DpaSummary extends \ExternalModules\AbstractExternalModule {
    use emLoggerTrait;
    public function __construct() {
        parent::__construct();
        // Other code to run when object is instantiated
    }

    public function redcap_save_record( int $project_id, $record, string $instrument, int $event_id, $group_id, $survey_hash, $response_id, $repeat_instance ): void
    {
        $this->emError("hello world");
        if (! array_key_exists('webauth_user1', $_POST)) {
            return;
        }
// todo save as EM project setting in the config Json
        $this->getProjectSetting( 'oncore-api-url');// TODO add project setting for the API token
        //Redcap Data privacy attestation API
        $KVS = \ExternalModules\ExternalModules::getModuleInstance('key-value-store');
        $api_token = $KVS->getValue($project_id, "API_TOKEN");

        $this_record = $_POST;
// begin starr nav - added September 2019 in support of an abbreviated form when requesting starr nav access
        if ($this_record['prj_type'] == 10) {
            $data = array(
                'record_id' => $record,
                'prj_project_title' => 'Prep to Research access to deID STARR-Nav',
                'prj_project_description' => 'Request for access to Research IT generated de-identified STARR-Nav data, a High Risk dataset, for internal use at Stanford',
                'prep_to_rsrch_phi' => 0,
                'approx_number_patients' => '4',
                'd_lab_results' => 2,
                'd_diag_proc' => 2,
                'd_medications' => 2,
                'd_demographics' => 2,
                'd_radiology' => 2,
                'd_clinical' => 2,
                'ex_us_inbound' => '0',
                'ex_us_outbound' => '0'
            );
            $this->putRecord($data, $api_token);
            $summary = "Prep to Research access to deID STARR-Nav\n\nRequest for access to Research IT generated de-identified STARR-Nav data, a High Risk dataset, for internal use at Stanford\n\n".
                "We will be internally using at Stanford the following types of data:\nDemographics (age, sex, etc.)\nLab or test results\nDiagnosis or procedure codes\nClinical narratives or non-imaging procedure reports\nPrescriptions or medications\nImaging data or radiology reports*".
                "Furthermore since we will be working with free text narratives we may be incidentally exposed to the following:\n1. Names\n3. Telephone numbers\n4. Address\n5. Dates more precise than year only\n7. Electronic mail addresses\n8. Medical record numbers\n18. Any other unique identifying number, characteristic, or code\n\n";
        } else {
            $summary = "";
        }
        // end starr nav


        if ($this_record['prj_type'] == 10 and $this_record['starr_nav_complete'] != 2 ) {
            $this->emDebug("STARR-Nav mid point, don't generate the summary just yet");
            exit("STARR-Nav mid point, don't generate the summary just yet");
        }

        $this->emDebug("HERE WE GO - line 74 record ".print_r($this_record, true));

///
/// // TODO shorten method. move logic to different method. A role of thumb is method should not exceed 20 lines.
        if ($this_record['summarygeneration_complete'] == 2 && $this_record['approval_complete'] == 2) {
            // check to see whether this invocation comes from a Privacy approval action
            $users = REDCap::getUserRights (  );
            $sunet_id = $_REQUEST['username'];
            $this->emDebug("server  ".print_r($_REQUEST, TRUE)." user role ".print_r($users[$sunet_id], TRUE));
            if (isset($users[$sunet_id]) &&( $users[$sunet_id]['role_name'] === 'Privacy') || $users[$sunet_id]['username'] === 'scweber'  ) {
                prepareAndSendNotification($this_record , TRUE);

            }
            $this->emDebug("summary already generated for ". $this_record['prj_project_title']." ". $this_record['prj_protocol_title']." by ".$this_record['webauth_user1']);
        }


// begin starr nav - added September 2019 in support of an abbreviated form when requesting starr nav access
        if ($this_record['prj_type'] == 10) {
            $data = array(
                'record_id' => $record,
                'prj_project_title' => 'Prep to Research access to deID STARR-Nav',
                'prj_project_description' => 'Request for access to Research IT generated de-identified STARR-Nav data, a High Risk dataset, for internal use at Stanford',
                'prep_to_rsrch_phi' => 0,
                'approx_number_patients' => '4',
                'd_lab_results' => 2,
                'd_diag_proc' => 2,
                'd_medications' => 2,
                'd_demographics' => 2,
                'd_radiology' => 2,
                'd_clinical' => 2,
                'ex_us_inbound' => '0',
                'ex_us_outbound' => '0'
            );
            $this->putRecord($data, $api_token);
            $summary = "Prep to Research access to deID STARR-Nav\n\nRequest for access to Research IT generated de-identified STARR-Nav data, a High Risk dataset, for internal use at Stanford\n\n".
                "We will be internally using at Stanford the following types of data:\nDemographics (age, sex, etc.)\nLab or test results\nDiagnosis or procedure codes\nClinical narratives or non-imaging procedure reports\nPrescriptions or medications\nImaging data or radiology reports*".
                "Furthermore since we will be working with free text narratives we may be incidentally exposed to the following:\n1. Names\n3. Telephone numbers\n4. Address\n5. Dates more precise than year only\n7. Electronic mail addresses\n8. Medical record numbers\n18. Any other unique identifying number, characteristic, or code\n\n";
        } else {
            $summary = "";
        }
// end starr nav


// carry on - the rest of this script generates a summary http://localhost/surveys/?s=THEX47FAHP7LDTFE
//        $baseurlsurvey = "https://redcap.stanford.edu/webauth/surveys/index.php?s=L3TRTT9EF9&edit=1&prior_version_record_num=".$this_record['record_id']."&prj_type=".$this_record['prj_type']."&irb_or_determination=".$this_record['irb_or_determination'];
// Ihab: how do I generate the survey URL without hard-coding it?
        // TODO move base survey url to config.json
        $baseurlsurvey = "http://localhost/surveys/?s=THEX47FAHP7LDTFE&prior_version_record_num=".$this_record['record_id']."&prj_type=".$this_record['prj_type']."&irb_or_determination=".$this_record['irb_or_determination'];
        // Get metadata for supplied API token
        $params = array(
            'token'=>$api_token,
            'content'=>'metadata',
            'format'=>'json',
        );
        $meta = $this->getFromApi($params);

        // retrieve data dictionary and turn it into two hashmaps by record name, one for project information,
        // the other for the checkboxes for data elements
        $meta_de = array();
        $meta2 = array(); // stash everything - used for rendering the attestation wording in the summary
        $editurl = $baseurlsurvey;

        // TODO create method to build edit url
        if ($this_record['prj_type'] == '1') {
            $editurl .= "&prj_protocol=".$this_record['prj_protocol']."&dtls_id=".$this_record['dtls_id']."&research_dececeased_only=".$this_record['research_dececeased_only'];
        } else {
            $editurl .= "&prj_project_description=".urlencode($this_record['prj_project_description']);
        }
        if ($this_record['prj_type'] == '7') {
            $editurl .= "&prj_qi_project_tracking_id=".$this_record['prj_qi_project_tracking_id'];
        }
        if ($this_record['prj_type'] == '99') {
            $editurl .= "&prj_other_type_desc=".urlencode($this_record['prj_other_type_desc']);
        }
        foreach ($meta as $value) {
            $fieldname = $value['field_name'];
            if (substr($fieldname, 0, 2) === 'd_' ) {
                $meta_de[$fieldname] = strip_tags($value['field_label']);
            }
            if (substr($fieldname, 0, 4) === 'prj_') {
//            $this->emDebug("BINGO ".$fieldname. ' val '.print_r($this_record[$fieldname], true));
                if ('prj_type' === $fieldname) {
//                $this->emDebug("LOOPING ");
                    for ( $i = 0; $i < 8; $i++) {
//                    $this->emDebug("LOOKING FOR prj_type___".$i);
                        if ($this_record['prj_type___' . $i] == 1) {
//                        $this->emDebug("FOUND IT");
                            $editurl .= "&prj_type___" . $i. "=1";
                        }
                    }
                    if ($this_record['prj_type___99' ] == 1) {
                        $editurl .= "&prj_type___99=1";
                    }
                } else {
                    if (strlen($this_record[$fieldname])> 0) {
                        $editurl .= "&" . $fieldname . "=" . urlencode($this_record[$fieldname]);
                    }
                }
            }
            $meta2[$fieldname] = strip_tags($value['field_label']);
        }

        if ($this_record['prj_type'] == 1 && $this_record['irb_or_determination'] == 1) {
            $this_record['is_irb'] = "IRB";
        } else {
            $this_record['is_irb'] = '';
        }

        // TODO move build summary logic to a separate method.
        // Build our summary
        $is_recruitment = false;
        foreach ($meta_de as $key => $value) {

            if ($this_record[$key . '___1'] == 1) {
                if (! $is_recruitment) {
                    $summary .= "We will be working with the following types of data in support of recruitment activities:\n";
                    $is_recruitment = true;
                }
                $summary .= $meta_de[$key] . "\n";

                $editurl .= "&" . $key . "___1=1";
            }
        }

        $is_internal_use = false;
        foreach ($meta_de as $key => $value) {

            if ($this_record[$key . '___2'] == 1) {
                if (! $is_internal_use) {
                    $summary .= "\nWe will be internally using at Stanford the following types of data:\n";
                    $is_internal_use = true;
                }
                $summary .= $meta_de[$key] . "\n";

                $editurl .= "&" . $key . "___2=1";
            }
        }

        $is_external_use = false;
        foreach ($meta_de as $key => $value) {

            if ($this_record[$key . '___3'] == 1) {
                if (! $is_external_use) {
                    $summary .= "\nWe will be disclosing outside Stanford the following types of data:\n";
                    $is_external_use = true;
                }
                $summary .= $meta_de[$key] . "\n";

                $editurl .= "&" . $key . "___3=1";
            }
        }

        // and if they are looking at narrative records
        if ($this_record['d_clinical___1'] === '1' || $this_record['d_radiology___1'] === 1 || $this_record['d_clinical___2'] === '1' || $this_record['d_radiology___2'] === 1  || $this_record['d_clinical___3'] === '1' || $this_record['d_radiology___3'] === 1  ) {
            $summary .= "\nFurthermore since we will be working with free text narratives we may be incidentally exposed to the following:\n1. Names\n3. Telephone numbers\n4. Address\n5. Dates more precise than year only\n7. Electronic mail addresses\n8. Medical record numbers\n18. Any other unique identifying number, characteristic, or code\n\n";
        }

        if ($this_record['approx_number_patients'] <> '') {
            $summary .= "\nApproximate number of patient records accessed for the study: " ;
            if ($this_record['approx_number_patients'] === '1') {
                $summary .= "fewer than 100\n";
            } else if ($this_record['approx_number_patients'] === '2') {
                $summary .= "100 to less than 1,000\n";
            } else if ($this_record['approx_number_patients'] === '3') {
                $summary .= "1,000 to less than 10,000\n";
            } else if ($this_record['approx_number_patients'] === '4') {
                $summary .= "10,000 to less than 100,000\n";
            } else if ($this_record['approx_number_patients'] === '5') {
                $summary .= "more than 100,000\n";
            }
        }

        if ($this_record['ex_us_inbound'] === '1') {
            $summary .= "\nWe will be collecting data outside the US, in " . $this_record['ex_us_inbound_countries'] ;
            $editurl .= "&ex_us_inbound=1&ex_us_inbound_countries=".urlencode($this_record['ex_us_inbound_countries']);
        } else {
            $editurl .= "&ex_us_inbound=0";
        }

        $summary .= "\n";

        if ($this_record['ex_us_outbound'] === '1') {
            $summary .= "\nWe will be sending data outside the US, to " . $this_record['ex_us_countries_2'] ;
            $editurl .= "&ex_us_outbound=1&ex_us_countries_2=".urlencode($this_record['ex_us_countries_2']);
        } else {
            $editurl .= "&ex_us_outbound=0";
        }

        $summary .= "\n";
        // now make a copy for the short version in the add-on attestation
        $short_summary = $summary;

        for ($i = 1; $i <= 18; $i++) {
            $avar = 'attest_' . $i ;
            if ($this_record[$avar. '___1'] == 1) {
                $summary .=  "\n". $meta2[$avar] . "\n";
            }
        }
        // TODO why do you need this?
        date_default_timezone_set('America/Vancouver');

        $signature = "\nThis statement was digitally signed '" . $this_record['signature'] . "' on "  . date('F jS Y \a\t h:i:s A') . ' by SUNetID ' . $this_record['webauth_user1'];
        $short_summary .= $signature;
        $summary .= $signature;

        // TODO move ldap url to config.json
        // TODO move ldap call to a separate method.
        // last but not least look up the display name of the webauthed user
        $ldapUrl = "https://krb5-ldap-app-kbwg24yjgq-uw.a.run.app/webtools/redcap-ldap/redcap_validator_web_service.php?token=0dWhFQtgZN7VkCnDyzsoyZFoZGqKE4oALWMgs2K6JBkRZWS1dN&exact=true&only=displayname,sudisplaynamefirst,sudisplaynamelast,sudisplaynamelf,mail,telephonenumber,suaffiliation,sugwaffiliation1,ou,telephonenumber,suprimaryorganizationid,susunetid&username=";


        // TODO use Guzzle client. Maybe create an object in the constructor so you do not have to init everytime you need to make a call.
        # Do LDAP Lookup
        $ldap = file_get_contents($ldapUrl . $this_record['webauth_user1']);
        //      $this->emDebug("ldap: $ldap", "DEBUG");

        $ldapResult = json_decode($ldap,true);
        //$this->emDebug(print_r($ldapResult));
        // expected format: Y-M-D H:M
        $timestamp = date('Y-m-d H:i');
        // yes the survey instrument has a hidden timestamp field called date_of_initial_completion
        // but if the survey results are hand-edited we want the summary to be re-generated and re-timestamped

        // now persist the actual summary
        $data = array(
            'record_id' => $record,
            'summary' => $summary,
            'short_summary_for_addon' => $short_summary,
            'displayname' => $ldapResult['user_displayname'],
            'edit_url' => $editurl,
            'summary_creation_timestamp' => $timestamp,
            'summarygeneration_complete' => 2,
            'is_irb' => $this_record['is_irb'],
        );
        $this->putRecord($data, $api_token);

// finally if this is a more recent version of an existing attestation, clear this 'is_current' flog on the earlier version
        if (isset($this_record['prior_version_record_num']) && strlen($this_record['prior_version_record_num']) > 0) {
            $data = array(
                'record_id' => $this_record['prior_version_record_num'],
                'is_most_recent' => '0'
            );
            $this->putRecord($data, $api_token);
        }

        $this->emDebug ('Data Privacy Attestation: ' . print_r($summary,true));


/*$_POST
 submit-action = "submit-btn-saverecord"
 hidden_edit_flag = "1"
 __old_id__ = "1"
 record_id = "1"
 date_of_initial_completion = "2017-12-18 16:01:00"
 webauth_user1 = "eriking"
 org_id = "WFPS sdfasdf"
 ldap_department = "Medicine - Med/Cardiovascular Medicine"
 prj_type = "1"
 case_number = ""
 prj_other_type_desc = ""
 irb_or_determination = "1"
 irb_or_determination___radio = "1"
 prj_protocol = "44604"
 dtls_id = "155577"
 prj_protocol_title = "Studies of cardiovascular and metabolic diseases using data from the STARR data warehouse"
 prj_faculty_sponsor = "Erik Ingelsson"
 uploaded_supporting_doc = ""
 prj_qi_project_tracking_id = ""
 approx_number_patients = ""
 anon = ""
 __chk__d_full_name_RC_1 = ""
 __chk__d_full_name_RC_2 = "2"
 __chk__d_full_name_RC_3 = ""
 __chk__d_geographic_RC_1 = ""
 __chk__d_geographic_RC_2 = "2"
 __chk__d_geographic_RC_3 = ""
 __chk__d_dates_RC_1 = ""
 __chk__d_dates_RC_2 = "2"
 __chk__d_dates_RC_3 = ""
 __chk__d_telephone_RC_1 = ""
 __chk__d_telephone_RC_2 = ""
 __chk__d_telephone_RC_3 = ""
 __chk__d_fax_RC_1 = ""
 __chk__d_fax_RC_2 = ""
 __chk__d_fax_RC_3 = ""
 __chk__d_email_RC_1 = ""
 __chk__d_email_RC_2 = ""
 __chk__d_email_RC_3 = ""
 __chk__d_ssn_RC_1 = ""
 __chk__d_ssn_RC_2 = ""
 __chk__d_ssn_RC_3 = ""
 __chk__d_mrn_RC_1 = ""
 __chk__d_mrn_RC_2 = ""
 __chk__d_mrn_RC_3 = ""
 __chk__d_beneficiary_num_RC_1 = ""
 __chk__d_beneficiary_num_RC_2 = ""
 __chk__d_beneficiary_num_RC_3 = ""
 __chk__d_insurance_num_RC_1 = ""
 __chk__d_insurance_num_RC_2 = ""
 __chk__d_insurance_num_RC_3 = ""
 __chk__d_certificate_num_RC_1 = ""
 __chk__d_certificate_num_RC_2 = ""
 __chk__d_certificate_num_RC_3 = ""
 __chk__d_vehicle_num_RC_1 = ""
 __chk__d_vehicle_num_RC_2 = ""
 __chk__d_vehicle_num_RC_3 = ""
 __chk__d_device_num_RC_1 = ""
 __chk__d_device_num_RC_2 = ""
 __chk__d_device_num_RC_3 = ""
 __chk__d_urls_RC_1 = ""
 __chk__d_urls_RC_2 = ""
 __chk__d_urls_RC_3 = ""
 __chk__d_ips_RC_1 = ""
 __chk__d_ips_RC_2 = ""
 __chk__d_ips_RC_3 = ""
 __chk__d_identifying_image_RC_1 = ""
 __chk__d_identifying_image_RC_2 = ""
 __chk__d_identifying_image_RC_3 = ""
 __chk__d_other_phi_RC_1 = ""
 __chk__d_other_phi_RC_2 = ""
 __chk__d_other_phi_RC_3 = ""
 __chk__d_hospital_costs_RC_1 = ""
 __chk__d_hospital_costs_RC_2 = ""
 __chk__d_hospital_costs_RC_3 = ""
 __chk__d_demographics_RC_1 = ""
 __chk__d_demographics_RC_2 = "2"
 __chk__d_demographics_RC_3 = ""
 __chk__d_lab_results_RC_1 = ""
 __chk__d_lab_results_RC_2 = "2"
 __chk__d_lab_results_RC_3 = ""
 __chk__d_diag_proc_RC_1 = ""
 __chk__d_diag_proc_RC_2 = "2"
 __chk__d_diag_proc_RC_3 = ""
 __chk__d_psych_eval_RC_1 = ""
 __chk__d_psych_eval_RC_2 = ""
 __chk__d_psych_eval_RC_3 = ""
 __chk__d_clinical_RC_1 = ""
 __chk__d_clinical_RC_2 = "2"
 __chk__d_clinical_RC_3 = ""
 __chk__d_medications_RC_1 = ""
 __chk__d_medications_RC_2 = "2"
 __chk__d_medications_RC_3 = ""
 __chk__d_radiology_RC_1 = ""
 __chk__d_radiology_RC_2 = "2"
 __chk__d_radiology_RC_3 = ""
 __chk__d_other_image_RC_1 = ""
 __chk__d_other_image_RC_2 = ""
 __chk__d_other_image_RC_3 = ""
 __chk__d_other_non_phi_RC_1 = ""
 __chk__d_other_non_phi_RC_2 = ""
 __chk__d_other_non_phi_RC_3 = ""
 __chk__d_clinical_deid_RC_1 = ""
 __chk__d_clinical_deid_RC_2 = ""
 __chk__d_clinical_deid_RC_3 = ""
 __chk__d_radiology_deid_RC_1 = ""
 __chk__d_radiology_deid_RC_2 = ""
 __chk__d_radiology_deid_RC_3 = ""
 de_identifier_other_desc = ""
 de_medical_other_desc = ""
 dicom_yn = ""
 research_dececeased_only = "0"
 research_dececeased_only___radio = "0"
 ex_us_inbound = "0"
 ex_us_inbound___radio = "0"
 ex_us_outbound = "0"
 ex_us_outbound___radio = "0"
 __chk__attest_1_RC_1 = "1"
 __chk__attest_2_RC_1 = "1"
 __chk__attest_3_RC_1 = "1"
 __chk__attest_4_RC_1 = "1"
 __chk__attest_5_RC_1 = "1"
 __chk__attest_6_RC_1 = "1"
 __chk__attest_7_RC_1 = "1"
 __chk__attest_14_RC_1 = "1"
 signature = "Erik Ingelsson"
 telephonenumber = "(650) 723-7614"
 mail = "eriking@stanford.edu"
 feedback = ""
 prior_version_record_num = ""
 is_most_recent = "1"
 is_most_recent___radio = "1"
 edit = ""
 phi_worksheet_complete = "2"
 empty-required-field = {array[20]}
 prep_to_rsrch_phi = ""
 hsr_determination = ""
 prep_rsrch_phi_justify = ""
 prj_project_title = ""
 prj_project_description = ""
 dicom_download_or_read = ""
 dicom_deid_ok = ""
 dicom_crosswalk_mrn = ""
 dicom_crosswalk_accession = ""
 dicom_dates = ""
 recruitment_approach = ""
 __chk__recruitment_approach_2_RC_2 = ""
 __chk__recruitment_approach_2_RC_3 = ""
 __chk__recruitment_approach_2_RC_4 = ""
 __chk__recruitment_approach_2_RC_88 = ""
 ex_us_inbound_countries = ""
 ex_us_countries_2 = ""
 data_use_agreement = ""
 __chk__attest_9_RC_1 = ""
 __chk__attest_11_RC_1 = ""
 __chk__attest_8_RC_1 = ""
 __chk__attest_12_RC_1 = ""
 __chk__attest_10_RC_1 = ""*/

    }
/*

       */
    function getFromApi($params) {

        $api_url = "//" .  $_SERVER['HTTP_HOST'] . "/api";

        $guzzle = new \GuzzleHttp\Client([
                'timeout' => 30,
                'connect_timeout' => 5,
                'verify' => $this->isDisableVerification(),
            ]
        );
        $response = $guzzle->get($api_url );

        $r = curl_init($api_url);
        curl_setopt($r, CURLOPT_POST, 1);
        curl_setopt($r, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
        $r_result = curl_exec($r);
        $r_error = curl_error($r);
        curl_close($r);
        if ($r_error) {
            $this->emDebug("Curl call failed ($r_error) with params (".json_encode($params).")", 'ERROR');
            exit;
        }
        $results = json_decode($r_result,true);
        //$this->emDebug("Results: ".print_r($results,true), "DEBUG");
        return $results;
    }

    /* Call this to store data */
    function putRecord($data, $api_token)
    {
        // TODO create a method to pull $api_url from either config.json or $_SERVER['HTTP_HOST']
        global $api_url;
        $params = array(
            'token' => $api_token,
            'content' => 'record',
            'format' => 'json',
            'type' => 'flat',
            'data' => json_encode(array($data))
        );
        // $this->emDebug('putRecord PARAMS: ' . print_r($params,true), "DEBUG");	//DEBUG

        // TODO use Guzzle Client for more details: https://docs.guzzlephp.org/en/stable/request-options.html
        //        $client = new \GuzzleHttp\Client([
        //                'timeout' => 30,
        //                'connect_timeout' => 5,
        //                'verify' => true/false,
        //            ]
        //        );
        //        $response = $client->post($api_url, [
        //            'debug' => false,
        //            'body' => json_encode($params),
        //            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        //        ]);

        $client = new  \GuzzleHttp\Client();
        $r = curl_init($api_url);
        curl_setopt($r, CURLOPT_POST, 1);
        curl_setopt($r, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
        $r_result = curl_exec($r);
        $r_error = curl_error($r);
        curl_close($r);
        if ($r_error) {
            $this->emDebug("PutRecord Curl call failed ($r_error) with params (" . json_encode($params) . ")", 'ERROR');
            exit;
        }
        $arr_result = json_decode($r_result, true);
        $this->emDebug("ArrResult: " . print_r($arr_result,true), 'DEBUG');
        $this->emDebug("Set ".implode(',',array_keys($data))." for record: ".$arr_result['count']);
    }


}
