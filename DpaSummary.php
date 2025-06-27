<?php

namespace Stanford\DpaSummary;

include_once 'emLoggerTrait.php';

use ExternalModules\ExternalModules;
use GuzzleHttp\Exception\GuzzleException;
use Project;
use REDCap;

class DpaSummary extends \ExternalModules\AbstractExternalModule
{
    use emLoggerTrait;

    private $baseurl;
    private $baseurlsurvey;

    public function __construct()
    {
        parent::__construct();
        // Other code to run when object is instantiated
    }

    private function getBaseUrl(): string
    {
        $this->baseurl = ($this->getProjectSetting('run-in-test-mode') ? "http://localhost/" : "https://redcap.stanford.edu/");
        return $this->baseurl;
    }

    private function getBaseUrlSurvey($this_record): string
    {
        $this->baseurlsurvey = $this->getBaseUrl() . ($this->getProjectSetting('run-in-test-mode') ? "" : "webauth/") . "/surveys/?s=";
        $this->baseurlsurvey .= $this->getProjectSetting('survey-url');
// carry on - the rest of this script generates a summary
// for testing use survey-url THEX47FAHP7LDTFE ;for prod use  L3TRTT9EF9
//     $baseurlsurvey = "https://redcap.stanford.edu/webauth/surveys/index.php?s=L3TRTT9EF9&edit=1&prior_version_record_num=".$this_record['record_id']."&prj_type=".$this_record['prj_type']."&irb_or_determination=".$this_record['irb_or_determination'];

        $this->baseurlsurvey .= "&prior_version_record_num=" . $this_record['record_id'] . "&prj_type=" . $this_record['prj_type'] . "&irb_or_determination=" . $this_record['irb_or_determination'];

        return $this->baseurlsurvey;
    }

    public function redcap_save_record(int $project_id, $record, string $instrument, int $event_id, $group_id, $survey_hash, $response_id, $repeat_instance): void
    {
        if (!array_key_exists('webauth_user1', $_POST)) {
            $this->emError("Unable to save record, unknown user");
            return;
        }
        $api_token = $this->getProjectSetting('api-token');

        $param = array(
            'project_id' => $project_id,
            'return_format' => 'array',
            'records' => [$record]
        );
        $data = \REDCap::getData($param);
        $this_record = $data[1][$event_id];
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
            $summary = "Prep to Research access to deID STARR-Nav\n\nRequest for access to Research IT generated de-identified STARR-Nav data, a High Risk dataset, for internal use at Stanford\n\n" .
                "We will be internally using at Stanford the following types of data:\nDemographics (age, sex, etc.)\nLab or test results\nDiagnosis or procedure codes\nClinical narratives or non-imaging procedure reports\nPrescriptions or medications\nImaging data or radiology reports*" .
                "Furthermore since we will be working with free text narratives we may be incidentally exposed to the following:\n1. Names\n3. Telephone numbers\n4. Address\n5. Dates more precise than year only\n7. Electronic mail addresses\n8. Medical record numbers\n18. Any other unique identifying number, characteristic, or code\n\n";
        } else {
            $summary = "";
        }
        // end starr nav


        if ($this_record['prj_type'] == 10 and $this_record['starr_nav_complete'] != 2) {
            $this->emDebug("STARR-Nav mid point, don't generate the summary just yet");
            exit("STARR-Nav mid point, don't generate the summary just yet");
        }

        $this->emDebug("HERE WE GO - line 57 record " . print_r($this_record, true));

///
        if ($this_record['summarygeneration_complete'] == 2 && $this_record['approval_complete'] == 2) {
            // check to see whether this invocation comes from a Privacy approval action
            $users = REDCap::getUserRights();
            $sunet_id = $_REQUEST['username'];
            $this->emDebug("server  " . print_r($_REQUEST, TRUE) . " user role " . print_r($users[$sunet_id], TRUE));
            if (isset($users[$sunet_id]) && ($users[$sunet_id]['role_name'] === 'Privacy') || $users[$sunet_id]['username'] === 'scweber') {
                prepareAndSendNotification($this_record, TRUE);

            }
            $this->emDebug("summary already generated for " . $this_record['prj_project_title'] . " " . $this_record['prj_protocol_title'] . " by " . $this_record['webauth_user1']);
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
            $summary = "Prep to Research access to deID STARR-Nav\n\nRequest for access to Research IT generated de-identified STARR-Nav data, a High Risk dataset, for internal use at Stanford\n\n" .
                "We will be internally using at Stanford the following types of data:\nDemographics (age, sex, etc.)\nLab or test results\nDiagnosis or procedure codes\nClinical narratives or non-imaging procedure reports\nPrescriptions or medications\nImaging data or radiology reports*" .
                "Furthermore since we will be working with free text narratives we may be incidentally exposed to the following:\n1. Names\n3. Telephone numbers\n4. Address\n5. Dates more precise than year only\n7. Electronic mail addresses\n8. Medical record numbers\n18. Any other unique identifying number, characteristic, or code\n\n";
        } else {
            $summary = "";
        }
// end starr nav
        $baseurlsurvey = $this->getBaseUrlSurvey($this_record);
        // Get metadata for supplied API token
        $params = array(
            'token' => $api_token,
            'content' => 'metadata',
            'format' => 'json',
        );
        $meta = $this->post($params);

        // retrieve data dictionary and turn it into two hashmaps by record name, one for project information,
        // the other for the checkboxes for data elements
        $meta_de = array();
        $meta2 = array(); // stash everything - used for rendering the attestation wording in the summary
        $editurl = $baseurlsurvey;

        if ($this_record['prj_type'] == '1') {
            $editurl .= "&prj_protocol=" . $this_record['prj_protocol'] . "&dtls_id=" . $this_record['dtls_id'] . "&research_dececeased_only=" . $this_record['research_dececeased_only']; // yes, that is how the variable is spelled
        } else {
            $editurl .= "&prj_project_description=" . urlencode($this_record['prj_project_description']);
        }
        if ($this_record['prj_type'] == '7') {
            $editurl .= "&prj_qi_project_tracking_id=" . $this_record['prj_qi_project_tracking_id'];
        }
        if ($this_record['prj_type'] == '99') {
            $editurl .= "&prj_other_type_desc=" . urlencode($this_record['prj_other_type_desc']);
        }
        foreach ($meta as $value) {
            $fieldname = $value['field_name'];
            if (substr($fieldname, 0, 2) === 'd_') {
                $meta_de[$fieldname] = strip_tags($value['field_label']);
            }

            $meta2[$fieldname] = strip_tags($value['field_label']);
        }

        if ($this_record['prj_type'] == 1 && $this_record['irb_or_determination'] == 1) {
            $this_record['is_irb'] = "IRB";
        } else {
            $this_record['is_irb'] = '';
        }

        // Build our summary
        $is_recruitment = false;
        foreach ($meta_de as $key => $value) {

            if ($this_record[$key ][1] == 1) {
                if (!$is_recruitment) {
                    $summary .= "We will be working with the following types of data in support of recruitment activities:\n";
                    $is_recruitment = true;
                }
                $summary .= $meta_de[$key] . "\n";

                $editurl .= "&" . $key . "___1=1";
            }
        }

        $is_internal_use = false;
        foreach ($meta_de as $key => $value) {

            if ($this_record[$key][2] == 1) {
                if (!$is_internal_use) {
                    $summary .= "\nWe will be internally using at Stanford the following types of data:\n";
                    $is_internal_use = true;
                }
                $summary .= $meta_de[$key] . "\n";

                $editurl .= "&" . $key . "___2=1";
            }
        }

        $is_external_use = false;
        foreach ($meta_de as $key => $value) {

            if ($this_record[$key][3] == 1) {
                if (!$is_external_use) {
                    $summary .= "\nWe will be disclosing outside Stanford the following types of data:\n";
                    $is_external_use = true;
                }
                $summary .= $meta_de[$key] . "\n";

                $editurl .= "&" . $key . "___3=1";
            }
        }

        // and if they are looking at narrative records
        if ($this_record['d_clinical'][1] == 1 || $this_record['d_radiology'][1] == 1 || $this_record['d_clinical'][2] == 1 || $this_record['d_radiology'][2] == 1 || $this_record['d_clinical'][3] == 1 || $this_record['d_radiology'][3] == 1) {
            $summary .= "\nFurthermore since we will be working with free text narratives we may be incidentally exposed to the following:\n1. Names\n3. Telephone numbers\n4. Address\n5. Dates more precise than year only\n7. Electronic mail addresses\n8. Medical record numbers\n18. Any other unique identifying number, characteristic, or code\n\n";
        }

        if ($this_record['approx_number_patients'] <> '') {
            $summary .= "\nApproximate number of patient records accessed for the study: ";
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
            $summary .= "\nWe will be collecting data outside the US, in " . $this_record['ex_us_inbound_countries'];
            $editurl .= "&ex_us_inbound=1&ex_us_inbound_countries=" . urlencode($this_record['ex_us_inbound_countries']);
        } else {
            $editurl .= "&ex_us_inbound=0";
        }

        $summary .= "\n";

        if ($this_record['ex_us_outbound'] === '1') {
            $summary .= "\nWe will be sending data outside the US, to " . $this_record['ex_us_countries_2'];
            $editurl .= "&ex_us_outbound=1&ex_us_countries_2=" . urlencode($this_record['ex_us_countries_2']);
        } else {
            $editurl .= "&ex_us_outbound=0";
        }

        $summary .= "\n";
        // now make a copy for the short version in the add-on attestation
        $short_summary = $summary;

        for ($i = 1; $i <= 18; $i++) {
            $avar = 'attest_' . $i;
            if ($this_record[$avar ][1] == 1) {
                $summary .= "\n" . $meta2[$avar] . "\n";
            }
        }
        date_default_timezone_set('America/Vancouver');

        $signature = "\nThis statement was digitally signed '" . $this_record['signature'] . "' on " . date('F jS Y \a\t h:i:s A') . ' by SUNetID ' . $this_record['webauth_user1'];
        $short_summary .= $signature;
        $summary .= $signature;

        // last but not least look up the display name of the webauthed user
        $ldapUrl = "http://127.0.0.1:8080/webtools/redcap-ldap/redcap_validator_web_service.php?token=0dWhFQtgZN7VkCnDyzsoyZFoZGqKE4oALWMgs2K6JBkRZWS1dN&exact=true&only=displayname,sudisplaynamefirst,sudisplaynamelast,sudisplaynamelf,mail,telephonenumber,suaffiliation,sugwaffiliation1,ou,telephonenumber,suprimaryorganizationid,susunetid&username=";

        # Do LDAP Lookup
        $ldap = file_get_contents($ldapUrl . $this_record['webauth_user1']);
        //      $this->emDebug("ldap: $ldap", "DEBUG");

        $ldapResult = json_decode($ldap, true);
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

        $this->emDebug('Data Privacy Attestation: ' . print_r($summary, true));

    }

    function getGuzzleClient(): \GuzzleHttp\Client
    {

        $guzzle = new \GuzzleHttp\Client([
                'timeout' => 30,
                'connect_timeout' => 5,
                'verify' => $this->getProjectSetting('verify-api-call'),
            ]
        );
        return $guzzle;
    }

    /* Call this to store data */
    function putRecord($data, $api_token)
    {
        $params = array(
            'token' => $api_token,
            'content' => 'record',
            'format' => 'json',
            'type' => 'flat',
            'data' => json_encode(array($data))
        );
        return $this->post($params);
    }

    /**
     * Global Post method
     * @param string $path
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface|void
     * @throws \Exception
     */
    public function post( array $data)
    {
        try {
            $api_url = $this->getBaseUrl() . "api/";
            $response = $this->getGuzzleClient()->post($api_url, [
                'debug' => false,
                'form_params' => $data,
                'headers' => ['Accept' => 'application/json'],
            ]);
            $body = $response->getBody()->getContents();
            return json_decode($body, true);
        } catch (GuzzleException $e) {
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                $responseBodyAsString = json_decode($response->getBody()->getContents(), true);
                throw new \Exception($responseBodyAsString['message']);
            } else {
                REDCap::logEvent('Metadata Guzzle Exception', $e->getMessage());
            }
        } catch (\Exception $e) {
            REDCap::logEvent('Metadata Guzzle Exception', $e->getMessage());
        }
    }

}
