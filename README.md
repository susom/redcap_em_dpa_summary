# DPA Summary

Replacement for plugins/approvals/privacy plugin

The Data Privacy Attestation, or DPA, was created about 15 years ago. Originally in paper form, then as a Word document, it was first implemented in REDCap probably some time around 2014.

The intent of the DPA is a formal listing of clinical data elements and PHI that a research project will work with. The IRB is primarily responsible for protecting human subjects. The Privacy Office is primarily responsible for protecting data privacy and preventing HIPAA breaches.  The missions of these two otherwise unrelated organizations intersect when research projects use clinical data for research purposes.  Todd arranged for the IRB to require a structured listing of all clinical data elements and PHI, so that the STARR team could reference this list and know what joint review by the IRB and Privacy Office determined to be permissible for use in a given research project.

As of November 2023, the DPA is still implemented as a REDCap plugin, though this project is intended to change that. The plugin is (and this EM will be) configured on REDCap PID 9883, "Data Privacy Attestation", which has been the primary attestation REDCap since 2018. There are multiple earlier REDCaps but only PID 4734 is also consulted for primary attestations by the STARR compliance API. Everyone who uses STARR Tools has filled out at least one, sometimes more, record(s) in PID 9883 or an add-on record in PID 12935.

This document how the system currently works and describes what to do when asked about DPAs.

## Plugin Operation
As of January 2024 we are still integrated with eProtocol using the plugin, namely, redcap.stanford.edu/plugins/approvals/privacy/index.php

That URL is hard-coded into the .jsp pages used to render eProtocol section 11b, the hyperlink "Data Privacy Attestation". eProtocol sends two additional query parameters, irb and version. So for example to call up the existing DPA for IRB 13655, go to redcap.stanford.edu/plugins/approvals/privacy/index.php?irb=13655&version=200. If there is no DPA yet in REDCap for the supplied IRB#, you see a new survey. Otherwise, you get a rendering of the information in the most recent DPA for that IRB. In this case, if you supply a number for the current version that is less than the approved version in the eProtocol system, you don't see an Edit button. If the number is larger, the Edit button is shown.  The idea here is that approved DPAs should be read-only.

Note the onload attribute in the HTML body tag in index.php:
```
<body onload="window.opener.postMessage(window.document.getElementById('attst').innerHTML,'https://eprotocol.stanford.edu/');">
```
And a summary div, 
```
<div id="attst" style="display: none"><?php echo $summary_for_irb ?></div>
```
which contains the summary content posted to eProtocol by the onload postMessage

In the .jsp pages for eProtocol an event listener is set up
```
window.addEventListener('message', processMessage, false);
```
And there is further code in their processMessage callback to take the text from the incoming message and insert it into a read-only field in eProtocol.

When the Edit button is clicked or the supplied IRB does not yet have a DPA, the HTML written back to the browser contains the following header
```
Location: https://redcap.stanford.edu/surveys/index.php?s=L3TRTT9EF9&prj_type=1& ... the irb and version number pre-filled
```
Note the details ID is a hidden field on the survey that is populated by the query params in the URL in the location header.

generatesummary.php is run automatically (as a plugin) by REDCap when survey results are saved. The same summary that gets inserted into eProtocol winds up in the Privacy instrument of the survey, so they can cross check.

Ihab has met with Arvind Taranath and his colleague James to review how this all works

## EM Operation

As of January 2024 the EM is not yet live. Before going live with this, please conduct testing with Arvind, arvind.taranath@stanford.edu.

To make changes to summary generation, edit DpaSummary.php in this repo.

## To tutor a new researcher on how to correctly use the system
Point new researchers to the extensive documentation on the [STARR Tools site](https://med.stanford.edu/starr-tools.html). The key points to convey are

1. They must always, always, start by engaging with the IRB. The IRB will let them know whether what they want to do is considered research or not.
1. Assuming they are writing an IRB protocol, they must click on the link in their IRB protocol document to fill out the DPA.
1. Only if they do not have an IRB protocol (e.g. they are still in the prep-to-research stage) should they go to their [Privacy Dashboard](https://redcap.stanford.edu/plugins/approvals/privacy/index.php) and use the "New" button to create a self-signed DPA. Self-signed DPAs are only good for 90 days and only give you access to a very small number of charts (50).

## To help a researcher complaining they can't amend their DPA in eProtocol
Send them to Privacy.  99 times out of 100 the problem is that Privacy has rejected their application, then they try to amend the IRB but the rejection prevents further editing.  This workflow is admittedly awkward but is designed to ensure that Privacy is kept in the loop every step of the process of getting approval.

## To review Primary Attestation Data
All recent primary attestations are in PID 9883. This project went live in late 2018.
There are a handful of legacy saved cohorts still referencing IRBs with DPAs in PID 4734, but eProtocol does not reference that
REDCap any more, so next time they amend their IRB they will wind up creating a new record in PID 9883.
To see what DPA is associated with an IRB, look it up on the [IRB Validity app](https://starr.med.stanford.edu/irb-validity/web/).
Version 3 DPAs are PID 9883, Version 2 are PID 4734.

## To review Add-On Attestation Data
As of Sept 2022 add-on attestations are all in PID 12935. This project went live in early 2019 after just a few weeks of trying but failing to capture add-on attestations in PID 9883. The link to an add-on attestation appears on the PI's list in their [Data Privacy Dashboard](https://redcap.stanford.edu/plugins/approvals/privacy/index.php) and always takes the form of https://redcap.stanford.edu/surveys/?s=8RWF73YTWA&protocol_number=<protocol_number>, e.g. https://redcap.stanford.edu/surveys/?s=8RWF73YTWA&protocol_number=27893

## To request changes to the DPA summary that shows up in the IRB
In the rare case where the IRB and Privacy agree that wording changes are needed, you will need to modify not only the REDCap project e.g. by adding a new question or altering the wording or answer options of an existing question, but also amend how the summary is generated. *The summary is created by this plugin.*

The RIC and the UPO are both able to edit PID 9883.

The summary takes the following form:

-------

### If PHI used for recruitment

We will be working with the following types of data in support of recruitment activities:
 {list of selected PHI}

### If PHI used internally
We will be internally using at Stanford the following types of data:
{list of selected PHI}

### If PHI disclosed
We will be disclosing outside Stanford the following types of data:
{list of selected PHI}

### If working with free text
Furthermore since we will be working with free text narratives we may be incidentally exposed to the following:
 1- Names
 3- Telephone numbers
 4- Address
 5- Dates more precise than year only
 7- Electronic mail addresses
 8- Medical record numbers
 18- Any other unique identifying number, characteristic, or code

### If collecting data outside US
We will be collecting data outside the US, in {listed countries}

### If sending data outside US

We will be sending data outside the US, to {listed countries}

-------


## To enable a new UPO team member to edit / approve DPAs
First ask them to log into REDCap to get their SUNetID registered in the system. Once they have registered, go to the User Rights page of PID 9883 and add them in the "Privacy" role

## To make changes to the DPA summary that shows up in the IRB
The DPA summary is generated by this REDCap EM, enabled on PID 9883.
