<?php
/**
 * Created by PhpStorm.
 * User: ahsuoy
 * Date: 4/10/2016
 * Time: 3:46 PM
 */
$app->group('/acton-nutshell', function() use ($app) {

    $app->get('/test-nutshell-email', function($request, $response, $args) use ($app) {
//        date_default_timezone_set('America/Los_Angeles');
        echo date_default_timezone_get();
        $date = date("D, j M Y G:i:s ")."+0500";
        print_r($date);

        $newEmailParams = array(

            "emailString"  => "From: Iterate Marketing <support@iteratemarketing.com>\nTo: demouser@testuser.com\nSubject: bar\nDate: ".$date."\n\nbar"


        );

        $sentEmail = $app->nutshellApiDev->call('newEmail', $newEmailParams);

        echo "<pre>";

        print_r($sentEmail);

    })->setName('test-nutshell-email');

    $app->get('/fromnutshell-toacton-update', function($request, $response, $args) use ($app) {

        ini_set('max_execution_time', 400);

// 					  $my_file = 'edit-info.txt';
// 						$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
// 						$data = "lead does not exist.";
// 						//foreach($searchUsers[0] as $assigneeKey => $assigneeVal) $data .= $assigneeKey . " => " . $assigneeVal;
// 						fwrite($handle, $data);

        $where = "entrytype='account' AND updated='0'";
        $entries = $app->db->simpleSelect('*', 'entries', $where);

        $entries = array();

        if($app->db->getNumRows() > 0) {

            while($entry = $app->db->fetchRow()) {

                $entries[] = $entry;

            }

        }

        if(count($entries) > 0) {

            $updatedEntries = array();

            foreach($entries as $entryKey => $entry) {

                $accountParams = array(

                    'accountId'  => $entry['entrynutid'],
                    'rev'        => null

                );

                $account = $app->nutshellApiDev->call('getAccount', $accountParams);
                $account = json_decode(json_encode($account), true);

                //print_r($account);
                //echo "<br>" . $entryKey . "<br>";

                if(strtolower($account['accountType']['name']) == 'customer') {

                    $updatedEntries[] = $entry['entryid'];

                    $accessToken = call_user_func_array($app->checkAccessExpires, array('body' => $app->actOnAccountDev->getAccountInfo(), 'currentActOnAccount' => $app->actOnAccountDev->getCurrentAccount()));

                    $recordId = $entry['recordid'];
                    $listId = explode(":", $entry['recordid']);

                    $params = array(

                        'listid'         => $listId[0],
                        'count'          => '1',
                        'offset'         => '0',
                        'modbefore'      => '',
                        'modeafter'      => '',
                        'createdbefore'  => '',
                        'createdafter'   => '',
                        //'fields'         => '',
                        'datequalifiers' => true

                    );

                    $headers = array('Accept' => 'application/json', 'Content-Type' => 'application/json');
                    $defaultHeaders = array('Authorization' => 'Bearer ' . $accessToken);

                    $contactRecords = array();
                    //if()
                    $contactRecords = $app->initAccess->actOnPullContactRecord($defaultHeaders, $headers, $listId[0], $recordId, $params);

                    if(count($contactRecords) > 0 && isset($contactRecords['E-mail Address']) && $contactRecords['E-mail Address'] != "" && isset($contactRecords['Pipeline Stage'])) {

                        $body = array(

                            'E-mail Address'     =>  $contactRecords['E-mail Address'],
                            'Pipeline Stage'     =>  'Client'

                        );

                        $updatedResults = $app->initAccess->actOnUpdateContactRecord($defaultHeaders, $headers, $listId[0], $recordId, $body);
                        $updatedResults = json_decode(json_encode($updatedResults), true);

                    }

                }

            }

            foreach($updatedEntries as $upentry) {

                $whereup = "entryid='".$upentry."'";
                $uparr = array('updated'=>1);
                $up = $app->db->Update('entries', $uparr, $whereup);

            }

        }

    })->setName('from-nutshell-to-acton-update');

    $app->get('/test-searchbymail', function($request, $response, $args) use ($app) {

        $email = 'Barbara.Appleton@Agfa.Com';
        $queryParams = array(

            'emailAddressString' => $email

        );

        $res = $app->nutshellApiDev->call('searchByEmail', $queryParams);
        $res = json_decode(json_encode($res), true);

        echo "<pre>";
        $accountTypeParams = array(

            'orderBy'          => 'name',
            'orderDirection'   => 'ASC',
            'limit'            => 100,
            'page'             => 1

        );

        $accountTypes = $app->nutshellApiDev->call('findAccountTypes', $accountTypeParams);
        $accountTypes = json_decode(json_encode($accountTypes), true);
        print_r($res['contacts'][0]['id']);

    })->setName('test-serachByEmail-function');

    $app->get('/test-user-team-search', function($request, $response, $args) use ($app) {

        $searchParams = array(

            'string'  => '',
            'limit'        => 1

        );

        $response = $app->nutshellApiDev->call('searchUsersAndTeams', $searchParams);

        $response = json_decode(json_encode($response), true);

        echo $response[0]['entityType'];

    })->setName('test-user-team-search');

    $app->post('/create-nutshell-people', function($request, $response, $args) use ($app) {

        ini_set('max_execution_time', 400);

        $formData = $request->getParsedBody();

        $queryParams = array(

            'emailAddressString' => $formData['E-mail_Address']

        );



        $res = $app->nutshellApiDev->call('searchByEmail', $queryParams);
        $res = json_decode(json_encode($res), true);

        if(!count($res['contacts'])) {

            $newContactParams = array(

                'contact'     => array(

                    'name'  => array(

                        'givenName'    => $formData['First_Name'],
                        'familyName'   => $formData['Last_Name'],
                        'displayName'  => $formData['First_Name'] . " " . $formData['Last_Name']
                    ),
                    'email'  => array($formData['E-mail_Address']),
                    'phone'  => array(

                        $formData['Business_Phone'],
                        'business' => $formData['Business_Phone']

                    )

                )

            );

            $app->nutshellApiDev->call('newContact', $newContactParams);

        }

    })->setName('create-new-contact-from-act-on-form');

    $app->post('/generalized-form-submission', function($request, $response, $args) use ($app) {

//         $formData = $request->getParsedBody();

        ini_set('max_execution_time', 400);

        if(isset($_REQUEST)) {


            $formData = $request->getParsedBody();

// 					  $my_file = 'test-sub.txt';
// 						$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
// 						$data = "lead does not exist.";
// 						foreach($formData as $assigneeKey => $assigneeVal) $data .= $assigneeKey . " => " . $assigneeVal;
// 						fwrite($handle, $data);

            $recordId1 = $formData['_SUBMITRECID'];
            $listId1 = explode(":", $recordId1);

            $accessToken1 = call_user_func_array($app->checkAccessExpires, array('body' => $app->actOnAccountDev->getAccountInfo(), 'currentActOnAccount' => $app->actOnAccountDev->getCurrentAccount()));


            $params1 = array(

                'listid'         => $listId1[0],
                'count'          => '1',
                'offset'         => '0',
                'modbefore'      => '',
                'modeafter'      => '',
                'createdbefore'  => '',
                'createdafter'   => '',
                //'fields'         => '',
                'datequalifiers' => true

            );

            $headers1 = array('Accept' => 'application/json');
            $defaultHeaders1 = array('Authorization' => 'Bearer ' . $accessToken1);

            $contactRecords1 = $app->initAccess->actOnPullContactRecord($defaultHeaders1, $headers1, $listId1[0], $recordId1, $params1);

            $noteData = "";

            if(isset($contactRecords1['Prior PRO-TREAD User'])) $noteData .= 'Prior PRO-TREAD User' . ' : ' . $contactRecords1['Prior PRO-TREAD User'] . "\n";
            if(isset($contactRecords1['Demo Request Products'])) $noteData .= 'Demo Request Products' . ' : ' . $contactRecords1['Demo Request Products'] . "\n";
            if(isset($contactRecords1['Current Training Program'])) $noteData .= 'Current Training Program' . ' : ' . $contactRecords1['Current Training Program'] . "\n";
            if(isset($contactRecords1['_FORM'])) $noteData .= '_FORM' . ' : ' . $contactRecords1['_FORM'] . "\n";
            if(isset($contactRecords1['Newsletter Subscriber'])) $noteData .= 'Newsletter Subscriber' . ' : ' . $contactRecords1['Newsletter Subscriber'] . "\n";
            if(isset($contactRecords1['Pipeline Stage'])) $noteData .= 'Pipeline Stage' . ' : ' . $contactRecords1['Pipeline Stage'] . "\n";
            if(isset($contactRecords1['Demo Request Notes'])) $noteData .= 'Demo Request Notes' . ' : ' . $contactRecords1['Demo Request Notes'] . "\n";
            if(isset($contactRecords1['Number of Employees'])) $noteData .= 'Number of Employees' . ' : ' . $contactRecords1['Number of Employees'] . "\n";

            $owner = array();
            $owner['entityType'] = "Users";
            $owner['id'] = 1;

            $searchParams = array(

                'string'   => (isset($contactRecords1['Owner Email']) && $contactRecords1['Owner Email'] != "") ? $contactRecords1['Owner Email'] : 'support@iteratemarketing.com',
                'limit'    => 1

            );


            $searchUsers = $app->nutshellApiDev->call('searchUsersAndTeams', $searchParams);
            $searchUsers = json_decode(json_encode($searchUsers), true);

            $owner['id'] = isset($searchUsers[0]['id']) ? $searchUsers[0]['id'] : 1;
            $owner['entityType'] = isset($searchUsers[0]['entityType']) ? $searchUsers[0]['entityType'] : 'Users';



// 					  die();

            $queryParams = array(

                'emailAddressString' => (isset($formData['E-mail_Address'])) ? $formData['E-mail_Address'] : ''

            );

            if(isset($formData['E-mail_Address'])) {


                $res = $app->nutshellApiDev->call('searchByEmail', $queryParams);
                $res = json_decode(json_encode($res), true);


                $industryParams = array(

                    'orderBy'          => 'name',
                    'orderDirection'   => 'ASC',
                    'limit'            => 100,
                    'page'             => 1

                );

                $industries = $app->nutshellApiDev->call('findIndustries', $industryParams);
                $industries = json_decode(json_encode($industries), true);
                $contactId = -1;
                $accountId = -1;
                $contactExist = 0;
                $accountExist = 0;
                $leadExist    = 0;

                if(count($res['contacts']) > 0) $contactExist = 1;
                if(count($res['accounts']) > 0) $accountExist = 1;

                if($contactExist == 0) {


                    $newContactPhone = array();
                    $newContactCustomFields = array();
                    if(isset($formData['Business_Phone']))
                        $newContactPhone[] = $formData['Business_Phone'];
                    if(isset($formData['Business_Phone']))
                        $newContactPhone['business'] = $formData['Business_Phone'];

                    $newsLetterSubscriber = (isset($formData['Newsletter_Subscriber'])) ? $formData['Newsletter_Subscriber'] : '';
                    $newContactParams = array(

                        'contact'     => array(

                            'name'  => array(

                                'givenName'    => $formData['First_Name'],
                                'familyName'   => $formData['Last_Name'],
                                'displayName'  => $formData['First_Name'] . " " . $formData['Last_Name']
                            ),
                            'email'  => array($formData['E-mail_Address']),
                            'phone'  => $newContactPhone,

                            'owner'         => $owner,
                            'customFields'  => array(

                                'Newsletter Subscriber' => $newsLetterSubscriber
                            ),
                            'tags'    => array('website')

                        )

                    );

                    if(isset($formData['Business_State'])) $newContactParams['contact']['address'][] = array('state'=>$formData['Business_State']);


// 									  $my_file = 'test-phone.txt';
// 										$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
// 										$data = "lead does not exist.";
// 										foreach($newContactPhone as $assigneeKey => $assigneeVal) $data .= $assigneeKey . " => " . $assigneeVal;
// 										fwrite($handle, $data);

                    $newContact = $app->nutshellApiDev->call('newContact', $newContactParams);

                    $createdContact = json_decode(json_encode($newContact), true);


                    $contactId = $newContact->id;

                    $entryData = array(
                        'entrytype'     => 'contact',
                        'recordid'      => $formData['_SUBMITRECID'],
                        'entrynutid'    => $contactId
                    );

                    $app->db->Insert('entries', $entryData);

                    $newNoteParams = array(

                        'entity'       => array(

                            'entityType'           => 'Contacts',
                            'id'                   => $contactId
                        ),

                        'note'         => $noteData
                    );

                    $createdNote = $app->nutshellApiDev->call('newNote', $newNoteParams);



                    $entryData = array(

                        'entrytype'        => 'note',
                        'recordid'         => $formData['_SUBMITRECID'],
                        'entrynutid'       => $createdNote->id,
                        'noteprojecttype'  => 'contact',
                        'noteprojectid'    => $contactId

                    );

                    $app->db->Insert('entries', $entryData);

                }else {

                    $contactId = $res['contacts'][0]['id'];
                    $entrynutid = $res['contacts'][0]['id'];



                    $where = "entrynutid='".$entrynutid."' AND entrytype='contact'";
                    $search = $app->db->simpleSelect('*', 'entries', $where);
                    $found = 0;
                    if($app->db->getNumRows() > 0) $found = 1;

                    if($found == 1) {

                        $upData = array(
                            'recordid' => $formData['_SUBMITRECID']
                        );

                        $app->db->Update('entries', $upData, $where);

                    }else {

                        $entryData = array(

                            'entrytype'     => 'contact',
                            'recordid'      => $formData['_SUBMITRECID'],
                            'entrynutid'    => $entrynutid

                        );

                        $app->db->Insert('entries', $entryData);
                    }

                    $where = "noteprojectid='".$entrynutid."' AND noteprojecttype='contact'";
                    $search = $app->db->simpleSelect('*', 'entries', $where);

                    $noteFound = 0;
                    $noteId = -1;
                    if($app->db->getNumRows() > 0) {

                        $noteFound = 1;

                        while($nid = $app->db->fetchRow()) {

                            $noteId = $nid['entrynutid'];
                        }
                    }

                    $getNote = $app->nutshellApiDev->call('getNote', array('noteId'=>$noteId, 'rev'=>null));

                    $noteEditParams = array(

                        'noteId'    => $noteId,
                        'rev'       => $getNote->rev,
                        'note'      => $noteData
                    );

                    if($noteId > 0) $editedNoteId = $app->nutshellApiDev->call('editNote', $noteEditParams);

                }

                if($accountExist == 0) {

                    $newAccountPhone = array();
                    $newAccountCustomFields = array();
                    if(isset($formData['Business_Phone']))
                        $newAccountPhone[] = $formData['Business_Phone'];
                    if(isset($formData['Business_Phone']))
                        $newAccountPhone['business'] = $formData['Business_Phone'];


                    $newsLetterSubscriber = (isset($formData['Newsletter_Subscriber'])) ? $formData['Newsletter_Subscriber'] : '';
                    $dotNumber = (isset($formData['DOT_Number'])) ? $formData['DOT_Number'] : '';
                    $employeeNumber = (isset($formData['Number_of_Employees'])) ? $formData['Number_of_Employees'] : '';
                    $industryId = 1;
                    foreach($industries as $key => $val) {

                        if($val['name'] == $formData['Company_Type']) $industryId = $val['id'];

                    }
                  
                    $contactRel = (isset($contactRecords1['Job Title']) && $contactRecords1['Job Title'] != '') ? $contactRecords1['Job Title'] : "First Contact";

                    $newAccountParams = array(

                        'account'  => array(

                            'name'           => $formData['Company'],
                            'industryId'  => $industryId,
                            'email'          => array($formData['E-mail_Address']),
                            'phone'          => $newAccountPhone,

                            'contacts'       => array(array('relationship'=>$contactRel, 'id'=>$contactId)),

                            'owner'          => $owner,
                            'customFields'   => array(

                                'Newsletter Subscriber' => $newsLetterSubscriber,
                                'Number of Employees'   => $employeeNumber,
                                'DOT'                   => $dotNumber
                            ),
                            'tags'           => array('website')

                        )

                    );

                    if(isset($formData['Business_State'])) $newAccountParams['account']['address'][] = array('state'=>$formData['Business_State']);

                    $newAccount = $app->nutshellApiDev->call('newAccount', $newAccountParams);
                    $accountId = $newAccount->id;

                    $entryData = array(
                        'entrytype'     => 'account',
                        'recordid'      => $formData['_SUBMITRECID'],
                        'entrynutid'    => $newAccount->id
                    );

                    $app->db->Insert('entries', $entryData);

                    $newNoteParams = array(

                        'entity'       => array(

                            'entityType'           => 'Accounts',
                            'id'                   => $accountId
                        ),

                        'note'         => $noteData
                    );

                    $createdNote = $app->nutshellApiDev->call('newNote', $newNoteParams);

                    $entryData = array(

                        'entrytype'        => 'note',
                        'recordid'         => $formData['_SUBMITRECID'],
                        'entrynutid'       => $createdNote->id,
                        'noteprojecttype'  => 'account',
                        'noteprojectid'    => $accountId

                    );

                    $app->db->Insert('entries', $entryData);


                }else {



                    $entrynutid = $res['accounts'][0]['id'];
                    $accountId = $res['accounts'][0]['id'];
                    $where = "entrynutid='".$entrynutid."' AND entrytype='account'";
                    $search = $app->db->simpleSelect('*', 'entries', $where);
                    $found = false;
                    if($app->db->getNumRows() > 0) $found = true;

                    if($found == 1) {

                        $upData = array(
                            'recordid' => $formData['_SUBMITRECID']
                        );

                        $app->db->Update('entries', $upData, $where);

                    }else {

                        $entryData = array(

                            'entrytype'     => 'account',
                            'recordid'      => $formData['_SUBMITRECID'],
                            'entrynutid'    => $entrynutid

                        );

                        $app->db->Insert('entries', $entryData);
                    }

                    $where = "noteprojectid='".$entrynutid."' AND noteprojecttype='account'";
                    $search = $app->db->simpleSelect('*', 'entries', $where);

                    $noteFound = 0;
                    $noteId = -1;
                    if($app->db->getNumRows() > 0) {

                        $noteFound = 1;

                        while($nid = $app->db->fetchRow()) {

                            $noteId = $nid['entrynutid'];
                        }
                    }

                    $getNote = $app->nutshellApiDev->call('getNote', array('noteId'=>$noteId, 'rev'=>null));

                    $noteEditParams = array(

                        'noteId'    => $noteId,
                        'rev'       => $getNote->rev,
                        'note'      => $noteData
                    );

                    if($noteId > 0)$editedNoteId = $app->nutshellApiDev->call('editNote', $noteEditParams);

                }

                if($leadExist == 0 && ($contactExist == 0 && $accountExist == 0)) {


                    // query lead for first time submission

                    // query leads

//                     $my_file = 'test-ca.txt';
//                     $handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
//                     $data = "lead: ";
//                     foreach($formData as $assigneeKey => $assigneeVal) $data .= $assigneeKey . " => " . $assigneeVal;
//                     fwrite($handle, $data);

                    $whereLeads1 = "recordid='".$formData['_SUBMITRECID']."' AND entrytype='lead'";
                    $leads1 = $app->db->simpleSelect('*', 'entries', $whereLeads1);
                    $existingLeadId1 = -1;
                    if($app->db->getNumRows() > 0) {

                        while($leadRow = $app->db->fetchRow()) {

                            $existingLeadId1 = $leadRow['entrynutid'];

                        }
                    }

                    if($existingLeadId1 < 0 && strtoupper($contactRecords1['Pipeline Stage']) == 'PROSPECT') {



                        $contactAccountType1 = '';

                        $currentAccount = $app->nutshellApiDev->call('getAccount', array('accountId'=>$accountId, 'rev'=>null));
                        $currentAccountDet = json_decode(json_encode($currentAccount), true);

                        $contactAccountType1 = $currentAccountDet['accountType']['name'];
                        // create lead
                        $assignee1 = array();

                        if(strtoupper($contactAccountType1) == 'CUSTOMER') {

                            $assignee1['entityType'] = "Users";
                            $assignee1['id'] = 1;

                            $searchParams1 = array(

                                'string'   => (isset($contactRecords1['Owner Email']) && $contactRecords1['Owner Email'] != "") ? $contactRecords1['Owner Email'] : 'support@iteratemarketing.com',
                                'limit'    => 1

                            );



                            $searchUsers1 = $app->nutshellApiDev->call('searchUsersAndTeams', $searchParams1);
                            $searchUsers1 = json_decode(json_encode($searchUsers1), true);

                            $assignee1['id'] = isset($searchUsers1[0]['id']) ? $searchUsers1[0]['id'] : 1;
                            $assignee1['entityType'] = isset($searchUsers1[0]['entityType']) ? $searchUsers1[0]['entityType'] : 'Users';


                            //$searchResult = $app->

                            $newLeadParams1 = array(

                                'lead'       => array(

                                    'contacts'   => array(

                                        array(

                                            'id' => $contactId

                                        )

                                    ),

                                    'accounts'   => array(

                                        array(

                                            'id' => $accountId

                                        )

                                    ),

                                    'assignee'    => $assignee1,
                                    'tags'        => array('website')

                                )
                            );

                        }else {

                            $newLeadParams1 = array(

                                'lead'       => array(

                                    'contacts'   => array(

                                        array(

                                            'id' => $contactId

                                        )

                                    ),

                                    'accounts'   => array(

                                        array(

                                            'id' => $accountId

                                        )

                                    ),
                                    'tags'        => array('website')

                                )
                            );

                        }

                        //$contactOwnerName

                        $newLead1 = $app->nutshellApiDev->call('newLead', $newLeadParams1);

                        $newEntry1 = array(

                            'entrytype'   => 'lead',
                            'recordid'    => $formData['_SUBMITRECID'],
                            'entrynutid'  => $newLead1->id

                        );

                        $entry = $app->db->Insert('entries', $newEntry1);

                    }else {



                        $leadInfo1 = $app->nutshellApiDev->call('getLead', array('leadId'=>$existingLeadId1, 'rev'=>null));
                        $leadInfo1 = json_decode(json_encode($leadInfo1), true);

                        $assigneeId1 = isset($leadInfo1['assignee']['id'])? $leadInfo1['assignee']['id']: -1;


                        if($assigneeId1 > -1) {

                            $assigneeInfo1 = $app->nutshellApiDev->call('getUser', array('userId'=>$assigneeId1, 'rev'=>null));
                            $assigneeInfo1 = json_decode(json_encode($assigneeInfo1), true);

                            $assigneeEmail1 = isset($assigneeInfo1['emails'][0]) ? $assigneeInfo1['emails'][0] : "";

                            if($assigneeEmail1 != "" && strtoupper($contactRecords1['Pipeline Stage']) == 'PROSPECT') {
                                date_default_timezone_set('America/Los_Angeles');
                                $emailDate = date("D, j M Y G:i:s ")."+0500";
                                $newEmailParams1 = array(


                                    "emailString"  => "From: Iterate Marketing <support@iteratemarketing.com>\nTo: ".$assigneeEmail1."\nSubject: alert\nDate: ".$emailDate."\n\nalert"

                                );

                                $sentEmail = $app->nutshellApiDev->call('newEmail', $newEmailParams1);
                                $sentEmail = json_decode(json_encode($sentEmail), true);

                            }
                        }

                        $newNoteParams1 = array(

                            'entity'       => array(

                                'entityType'           => 'Leads',
                                'id'                   => $existingLeadId1
                            ),

                            'note'         => $noteData."@[Users:".$assigneeInfo1['id']."]",
                            'noteMarkup'   => $noteData."@[Users:".$assigneeInfo1['id']."]"
                        );


                        $where1 = "noteprojectid='".$existingLeadId1."' AND noteprojecttype='lead'";
                        $search1 = $app->db->simpleSelect('*', 'entries', $where1);

                        $noteFound1 = 0;
                        $noteId1 = -1;
                        if($app->db->getNumRows() > 0) {

                            $noteFound1 = 1;

                            while($nid = $app->db->fetchRow()) {

                                $noteId1 = $nid['entrynutid'];
                            }
                        }
                        if($noteId1 > -1) {

                            $getNote1 = $app->nutshellApiDev->call('getNote', array('noteId'=>$noteId1, 'rev'=>null));

                            $noteEditParams1 = array(

                                'noteId'     => $noteId1,
                                'rev'        => $getNote1->rev,
                                'note'       => $noteData."@[Users:".$assigneeInfo1['id']."]",
                                'noteMarkup' => $noteData."@[Users:".$assigneeInfo1['id']."]"
                            );

                            $editedNote1 = $app->nutshellApiDev->call('editNote', $noteEditParams1);
                        }else {

                            $createdNote1 = $app->nutshellApiDev->call('newNote', $newNoteParams1);

                            $entryData = array(

                                'entrytype'        => 'note',
                                'recordid'         => $formData['_SUBMITRECID'],
                                'entrynutid'       => $createdNote1->id,
                                'noteprojecttype'  => 'lead',
                                'noteprojectid'    => $existingLeadId1

                            );

                            $app->db->Insert('entries', $entryData);

                        }

                    }

                }

                if($contactExist == 1 || $accountExist == 1) {



                    $contactAccountType = '';
                    $contactAssigneeId = -1;

                    $recordId = $formData['_SUBMITRECID'];
                    $listId = explode(":", $recordId);

                    $accessToken = call_user_func_array($app->checkAccessExpires, array('body' => $app->actOnAccountDev->getAccountInfo(), 'currentActOnAccount' => $app->actOnAccountDev->getCurrentAccount()));

                    $params = array(

                        'listid'         => $listId[0],
                        'count'          => '1',
                        'offset'         => '0',
                        'modbefore'      => '',
                        'modeafter'      => '',
                        'createdbefore'  => '',
                        'createdafter'   => '',
                        //'fields'         => '',
                        'datequalifiers' => true

                    );

                    $headers = array('Accept' => 'application/json');
                    $defaultHeaders = array('Authorization' => 'Bearer ' . $accessToken);

                    $contactRecords = $app->initAccess->actOnPullContactRecord($defaultHeaders, $headers, $listId[0], $recordId, $params);


//                     $my_file = 'test-thi.txt';
//                     $handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
//                     $data = "fdfdfd";
//                     //foreach($newEmailParams as $cKey => $cVal)  $data .= $cKey . " => " . $cVal;
//                     fwrite($handle, $data);

                    if($contactExist == 1) {


                        $editContactPhone = array();
                        $editContactCustomFields = array();
                        if(isset($formData['Business_Phone']) && $formData['Business_Phone'] != '')
                            $editContactPhone[] = $formData['Business_Phone'];
                        else if($contactRecords['Business Phone'] != '') $editContactPhone[] = $contactRecords['Business Phone'];
                        if(isset($formData['Business_Phone']) && $formData['Business_Phone'] != '')
                            $editContactPhone['business'] = $formData['Business_Phone'];
                        else if($contactRecords['Business Phone'] != '') $editContactPhone['business'] = $contactRecords['Business Phone'];

                        $newsLetterSubscriber = (isset($formData['Newsletter_Subscriber'])) ? $formData['Newsletter_Subscriber'] : $contactRecords['Newsletter Subsriber'];

                        $oldContact = $app->nutshellApiDev->call('getContact', array('contactId' => $contactId, 'rev' => null));
                        $oldContactDet = json_decode(json_encode($oldContact), true);
                        $oldContactRev = $oldContact->rev;


                        $contactParams = array(

                            'contact'        => array(

                                'name'  => array(

                                    'givenName'    => $contactRecords['First Name'],
                                    'familyName'   => $contactRecords['Last Name'],
                                    'displayName'  => $contactRecords['First Name'] . " " . $contactRecords['Last Name']

                                ),

                                'phone'          => $editContactPhone,

                                'accounts'          => array(

                                    array(

                                        'id'              => $accountId,
                                        'relationship'    => $contactRecords['Job Title']

                                    )

                                ),


                                'owner'       => $owner,
                                'customFields' => array(

                                    'Newsletter Subscriber'   => $newsLetterSubscriber
                                )


                            )

                        );

                        if(isset($formData['Business_State'])) $contactParams['contact']['address'][] = array('state'=>$formData['Business_State']);
                        else $contactParams['contact']['address'][] = array('state'=>$contactRecords['Business State']);

                        if(!in_array("website", $oldContactDet['tags'])) $contactParams['contact']['tags'] = array('website');



                        $editContactParams = array(

                            'contactId'       => $contactId,
                            'rev'             => $oldContactRev,
                            'contact'         => $contactParams['contact']
                        );

                        $editedContact = $app->nutshellApiDev->call('editContact', $editContactParams);
                        $editedContact = json_decode(json_encode($editedContact), true);
                        
                    }

                    if($accountExist == 1) {

                        $editAccountPhone = array();
                        $editAccountCustomFields = array();
                        if(isset($formData['Business_Phone']) && $formData['Business Phone'] != '')
                            $editAccountPhone[] = $formData['Business_Phone'];
                        else if($contactRecords['Business Phone'] != '') $editAccountPhone[] = $contactRecords['Business Phone'];
                        if(isset($formData['Business_Phone']) && $formData['Business Phone'] != '')
                            $editAccountPhone['business'] = $formData['Business_Phone'];
                        else if($contactRecords['Business Phone'] != '') $editAccountPhone['business'] = $contactRecords['Business Phone'];

                        $newsLetterSubscriber = (isset($formData['Newsletter_Subscriber'])) ? $formData['Newsletter_Subscriber'] : $contactRecords['Newsletter Subscriber'];
                        $dotNumber = (isset($formData['DOT_Number'])) ? $formData['DOT_Number'] : $contactRecords['DOT Number'];
                        $employeeNumber = (isset($formData['Number_of_Employees'])) ? $formData['Number_of_Employees'] : $contactRecords['Number of Employees'];

                        $industryId = 1;
                        foreach($industries as $key => $val) {

                            if($val['name'] == $contactRecords['Company Type']) $industryId = $val['id'];

                        }

                        $oldAccount = $app->nutshellApiDev->call('getAccount', array('accountId' => $accountId, 'rev' => null));
                        $oldAccountDet = json_decode(json_encode($oldAccount), true);

                        $contactAccountType = $oldAccountDet['accountType']['name'];
                        $oldAccountRev = $oldAccount->rev;

                        $accountParams = array(

                            'account'        => array(

                                'name'           => $contactRecords['Company'],
                                'industryId'     => $industryId,

                                'phone'          => $editAccountPhone,

                                'contacts'          => array(

                                    array(

                                        'id'              => $contactId,
                                        'relationship'    => $contactRecords['Job Title']

                                    )

                                ),
                                'customFields'      => array(

                                    'DOT'                   => $dotNumber,
                                    'Number of Employees'   => $employeeNumber,
                                    'Newsletter Subscriber' => $newsLetterSubscriber

                                ),

                                'owner'              => $owner
                            )

                        );

                        if(!in_array("website", $oldAccountDet['tags'])) $accountParams['account']['tags'] = array('website');

                        if(isset($formData['Business_State'])) $accountParams['account']['address'][] = array('state'=>$formData['Business_State']);
                        else $accountParams['account']['address'][] = $contactRecords['Business State'];
                        $editAccountParams = array(

                            'accountId'       => $accountId,
                            'rev'             => $oldAccountRev,
                            'account'         => $accountParams['account']
                        );

                        $editedAccount = $app->nutshellApiDev->call('editAccount', $editAccountParams);
                        $editedAccount = json_decode(json_encode($editedAccount), true);

                    }


                    if($accountExist == 1 || $contactExist == 1) {
                        
                        // query leads

                        $whereLeads = "recordid='".$formData['_SUBMITRECID']."' AND entrytype='lead'";
                        $leads = $app->db->simpleSelect('*', 'entries', $whereLeads);
                        $existingLeadId = -1;
                        if($app->db->getNumRows() > 0) {

                            while($leadRow = $app->db->fetchRow()) {

                                $existingLeadId = $leadRow['entrynutid'];

                            }
                        }

                        if($existingLeadId < 0 && strtoupper($contactRecords['Pipeline Stage']) == 'PROSPECT') {

                            // create lead
                            $assignee = array();

                            if(strtoupper($contactAccountType) == 'CUSTOMER') {

                                $assignee['entityType'] = "Users";
                                $assignee['id'] = 1;

                                $searchParams = array(

                                    'string'   => (isset($contactRecords['Owner Email']) && $contactRecords['Owner Email'] != "") ? $contactRecords['Owner Email'] : 'support@iteratemarketing.com',
                                    'limit'    => 1

                                );



                                $searchUsers = $app->nutshellApiDev->call('searchUsersAndTeams', $searchParams);
                                $searchUsers = json_decode(json_encode($searchUsers), true);

                                $assignee['id'] = isset($searchUsers[0]['id']) ? $searchUsers[0]['id'] : 1;
                                $assignee['entityType'] = isset($searchUsers[0]['entityType']) ? $searchUsers[0]['entityType'] : 'Users';


                                //$searchResult = $app->

                                $newLeadParams = array(

                                    'lead'       => array(

                                        'contacts'   => array(

                                            array(

                                                'id' => $contactId

                                            )

                                        ),

                                        'accounts'   => array(

                                            array(

                                                'id' => $accountId

                                            )

                                        ),

                                        'assignee'    => $assignee,
                                        'tags'        => array('website')

                                    )
                                );

                            }else {

                                $newLeadParams = array(

                                    'lead'       => array(

                                        'contacts'   => array(

                                            array(

                                                'id' => $contactId

                                            )

                                        ),

                                        'accounts'   => array(

                                            array(

                                                'id' => $accountId

                                            )

                                        ),
                                        'tags'        => array('website')

                                    )
                                );

                            }

                            //$contactOwnerName

                            $newLead = $app->nutshellApiDev->call('newLead', $newLeadParams);

                            $newEntry = array(

                                'entrytype'   => 'lead',
                                'recordid'    => $formData['_SUBMITRECID'],
                                'entrynutid'  => $newLead->id

                            );

                            $entry = $app->db->Insert('entries', $newEntry);

                        }else {



                            $leadInfo = $app->nutshellApiDev->call('getLead', array('leadId'=>$existingLeadId, 'rev'=>null));
                            $leadInfo = json_decode(json_encode($leadInfo), true);

                            $assigneeId = isset($leadInfo['assignee']['id'])? $leadInfo['assignee']['id']: -1;


                            if($assigneeId > -1) {

                                $assigneeInfo = $app->nutshellApiDev->call('getUser', array('userId'=>$assigneeId, 'rev'=>null));
                                $assigneeInfo = json_decode(json_encode($assigneeInfo), true);

                                $assigneeEmail = isset($assigneeInfo['emails'][0]) ? $assigneeInfo['emails'][0] : "";



                                if($assigneeEmail != "" && strtoupper($contactRecords['Pipeline Stage']) == 'PROSPECT') {

                                    date_default_timezone_set('America/Los_Angeles');
                                    $emailDate = date("D, j M Y G:i:s ")."+0500";
                                    $newEmailParams = array(


                                        "emailString"  => "From: Iterate Marketing <support@iteratemarketing.com>\nTo: ".$assigneeEmail."\nSubject: alert\nDate: \n\nalert"

                                    );

                                    $sentEmail = $app->nutshellApiDev->call('newEmail', $newEmailParams);
                                    $sentEmail = json_decode(json_encode($sentEmail), true);

                                }
                            }

                            $newNoteParams1 = array(

                                'entity'       => array(

                                    'entityType'           => 'Leads',
                                    'id'                   => $existingLeadId
                                ),

                                'note'         => $noteData."@[Users:".$assigneeInfo['id']."]",
                                'noteMarkup'   => $noteData."@[Users:".$assigneeInfo['id']."]"
                            );

                            $where1 = "noteprojectid='".$existingLeadId1."' AND noteprojecttype='lead'";
                            $search1 = $app->db->simpleSelect('*', 'entries', $where1);

                            $noteFound1 = 0;
                            $noteId1 = -1;
                            if($app->db->getNumRows() > 0) {

                                $noteFound1 = 1;

                                while($nid = $app->db->fetchRow()) {

                                    $noteId1 = $nid['entrynutid'];
                                }
                            }
                            if($noteId1 > -1) {

                                $getNote1 = $app->nutshellApiDev->call('getNote', array('noteId'=>$noteId1, 'rev'=>null));

                                $noteEditParams1 = array(

                                    'noteId'     => $noteId1,
                                    'rev'        => $getNote1->rev,
                                    'note'       => $noteData."@[Users:".$assigneeInfo['id']."]",
                                    'noteMarkup' => $noteData."@[Users:".$assigneeInfo['id']."]"
                                );

                                $editedNote1 = $app->nutshellApiDev->call('editNote', $noteEditParams1);
                            }else {

                                $createdNote1 = $app->nutshellApiDev->call('newNote', $newNoteParams1);

                                $entryData = array(

                                    'entrytype'        => 'note',
                                    'recordid'         => $formData['_SUBMITRECID'],
                                    'entrynutid'       => $createdNote1->id,
                                    'noteprojecttype'  => 'lead',
                                    'noteprojectid'    => $existingLeadId1

                                );

                                $app->db->Insert('entries', $entryData);

                            }
                        }


                    }


                }

            }else {

                // Form which does not have email address

                if(isset($formData['_SUBMITRECID'])) {

                    $recordId = $formData['_SUBMITRECID'];
                    $where = "recordid='".$recordId."'";

                    $existingRecords = $app->db->simpleSelect('*', 'entries', $where);
                    $existingRecordsArray = array();

                    if($app->db->getNumRows() > 0) {

                        while($recordRow = $app->db->fetchRow()) {

                            $existingRecordsArray[$recordRow['entrytype']] = $recordRow['entrynutid'];
                        }
                    }



                    // existing contact data

                    $contactData = array();
                    $accountData = array();
                    $leadData = array();
                    $noteInfo = array();

                    if(isset($existingRecordsArray['contact'])) $contactData = $app->nutshellApiDev->call('getContact', array('contactId'=>$existingRecordsArray['contact'], 'rev'=>null));
                    if(isset($existingRecordsArray['account'])) $accountData = $app->nutshellApiDev->call('getAccount', array('accountId'=>$existingRecordsArray['account'], 'rev'=>null));
                    if(isset($existingRecordsArray['lead'])) $leadData = $app->nutshellApiDev->call('getLead', array('leadId'=>$existingRecordsArray['lead'], 'rev'=>null));
                    if(isset($existingRecordsArray['note'])) $noteInfo = $app->nutshellApiDev->call('getNote', array('noteId'=>$existingRecordsArray['note'], 'rev'=>null));

                    if(isset($contactData->id) && isset($accountData->id)) {

                        $contactUpdateData = array(

                            'contact'       => array(

                                'accounts'   => array(


                                )
                            )
                        );

                        if(isset($contactRecords1['Business State'])) $contactUpdateData['contact']['address']['state'] = $contactRecords1['Business State'];
                        if(isset($formData['Business_State'])) $contactUpdateData['contact']['address']['state'] = $formData['Business_State'];

                        if(isset($contactRecords1['Business Phone'])) $contactUpdateData['contact']['phone']['business'] = $contactRecords1['Business Phone'];
                        if(isset($formData['Business_Phone'])) $contactUpdateData['contact']['phone']['business'] = $formData['Business_Phone'];

                        if(isset($accountData->id) && isset($formData['Job_Title'])) $contactUpdateData['contact']['accounts'][] = array(

                            'id'             => $accountData->id,
                            'relationship'   => $formData['Job_Title']
                        );

                        if(isset($contactRecords1['Newsletter Subscriber'])) $contactUpdateData['contact']['customFields']['Newsletter Subscriber'] = $contactRecords1['Newsletter Subscriber'];
                        if(isset($formData['Newsletter_Subscriber'])) $contactUpdateData['contact']['customFields']['Newsletter Subscriber'] = $formData['Newsletter_Subscriber'];

                        $editContactParams = array(
                            'contactId'         => $contactData->id,
                            'rev'               => $contactData->rev,
                            'contact'           => $contactUpdateData['contact']
                        );

                        $updatedContact = $app->nutshellApiDev->call('editContact', $editContactParams);


                    }

                    if(isset($accountData->id)) {

                        $accountUpdateData = array(

                            'account'         => array(


                            )

                        );


                        if(isset($contactRecords1['Business State'])) $accountUpdateData['account']['address']['state'] = $contactRecords1['Business State'];
                        if(isset($formData['Business_State'])) $accountUpdateData['account']['address']['state'] = $formData['Business_State'];

                        if(isset($contactRecords1['Business Phone'])) $accountUpdateData['account']['phone']['business'] = $contactRecords1['Business Phone'];
                        if(isset($formData['Business_Phone'])) $accountUpdateData['account']['phone']['business'] = $formData['Business_Phone'];

                        if(isset($contactData->id) && isset($formData['Job_Title'])) $accountUpdateData['account']['contacts'] = array(

                            array(
                                'id'=>$contactData->id,
                                'relationship'=>$formData['Job_Title']
                            )
                        );
                        if(isset($formData['DOT_Number']) || isset($formData['Number_of_Employees'])) {

                            if(isset($formData['DOT_Number']))$accountUpdateData['account']['customFields']['DOT'] = $formData['DOT_Number'];
                            if(isset($formData['Number_of_Employees']))$accountUpdateData['account']['customFields']['Number of Employees'] = $formData['Number_of_Employees'];
                            if(isset($contactRecords1['Newsletter Subscriber'])) $contactUpdateData['contact']['customFields']['Newsletter Subscriber'] = $contactRecords1['Newsletter Subscriber'];
                            if(isset($formData['Newsletter_Subscriber']))$accountUpdateData['account']['customFields']['Newsletter Subscriber'] = $formData['Newsletter_Subscriber'];
                        }


                        $editAccountParams = array(

                            'accountId'       => $accountData->id,
                            'rev'             => $accountData->rev,
                            'account'         => $accountUpdateData['account']
                        );

                        $updatedAccount = $app->nutshellApiDev->call('editAccount', $editAccountParams);


                    }

                    if(isset($noteInfo->id)) {


// 											  $my_file = 'note-data.txt';
// 										    $handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
// 										    $data = $noteData;
// 										    //foreach($editAccountParams['account']['customFields'] as $cKey => $cVal)  $data .= $cKey . " => " . $cVal;
// 										    fwrite($handle, $data);

                        $editNoteParams = array(

                            'noteId'    => $noteInfo->id,
                            'rev'       => $noteInfo->rev,
                            'note'      => $noteData

                        );

                        $updatedNote = $app->nutshellApiDev->call('editNote', $editNoteParams);
                    }

                }

            }


        }


    })->setName('generalized-form-submission');

    $app->post('/sales-requests/iframe/sales-drs-2', function() {


//         if(isset($_REQUEST)) {

//             $my_file = 'keyvals2.txt';
//             $handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
//             $data = "";

//             foreach($_REQUEST as $key => $request) {

//                 $data .= $key . " => " . $request . '\n';

//             }

//             fwrite($handle, $data);



//         }

    });

});

$app->group('/acton', function() use ($app) {

    $app->get('/lists', function($request, $response, $args) use ($app) {

        $accessToken = call_user_func_array($app->checkAccessExpires, array('body' => $app->actOnAccountClient->getAccountInfo(), 'currentActOnAccount' => $app->actOnAccountClient->getCurrentAccount()));

        $headers = array('Accept' => 'application/json');
        $defaultHeaders = array('Authorization' => 'Bearer ' . $accessToken);
        $params = array(

            'listingtype'   => 'CONTACT_LIST',
            'count'         => '200',
            'offset'        => '0'

        );

        $lists = $app->initAccess->actOnList($defaultHeaders, $headers, $params);

        $app->initAccess->createListsTable('lists');

        foreach($lists['body']['result'] as $listKey => $list) {

            $params = $list;
            $params['updateTime'] = time();
            $app->initAccess->insertInto($params, 'lists','id');

        }

        $listIds = $app->initAccess->getValuesByKey('lists', 'folderName', array('Tradeshows', 'NTPC', 'Default Folder'));

        ini_set('max_execution_time', 400);

        foreach($listIds as $listKey => $list) {

            foreach($list as $key => $listId) {

                $accessToken = call_user_func_array($app->checkAccessExpires, array('body' => $app->actOnAccountClient->getAccountInfo(), 'currentActOnAccount' => $app->actOnAccountClient->getCurrentAccount()));

                $headers = array('Accept' => 'application/json');

                $defaultHeaders = array('Authorization' => 'Bearer ' . $accessToken);

                $params = array(

                    'listid'         => $listId,
                    'count'          => '1000',
                    'offset'         => '0',
                    'modbefore'      => '',
                    'modeafter'      => '',
                    'createdbefore'  => '',
                    'createdafter'   => '',
                    //'fields'         => '',
                    'datequalifiers' => true

                );

                $contacts = $app->initAccess->actOnListDownLoadById($defaultHeaders, $headers, $params);

                echo "<pre>";

                foreach($contacts as $contactKey => $val) {

                    $contactListId = $val['body']['listId'];

                    foreach($val['body']['data'] as $newContactKey => $newContactData) {


                        foreach($newContactData as $columnKey => $columnVal) {

                            $newContactMeta = array(

                                'key'                             => $val['body']['headers'][$columnKey],
                                'value'                           => $columnVal,
                                'contactLegacyId'                 => $newContactData[0],
                                'updateTime'                      => time(),
                                'updateLevel'                     => 0

                            );

                            echo "new contact meta array<br>";
                            print_r($newContactMeta);

                        }

                        $newContact = array(

                            'legacyId'            => $newContactData[0],
                            'listId'              => $contactListId,
                            'updateTime'          => time(),
                            'updateLevel'         => 0

                        );

                        echo "new contact array<br>";
                        print_r($newContact);
                    }
                }

            }
        }

    });

});

$app->group('/nutshell', function() use ($app) {

    $app->get('/', function($request, $response, $args) use ($app) {

        echo "welcome to nutshell.";


    })->setName('nutshell-home');

    $app->get('/create-user', function($request, $response, $args) use ($app) {

        $newUserParams = array(

            'user'    => array(

                'firstName'       => 'spencer',
                'lastName'        => 'Hill',
                'password'        => 'password',
                'emails'          => array('demouser@testuser.com'),
                'isEnabled'       => true,
                'isAdministrator' => false,
                'teams'           => array(

                    'viewAll'  => array(),
                    'viewOwn'  => array(),
                    'viewTeam' => array()
                ),
                'sendInvite'      => false
            )
        );

        $res = $app->nutshellApiDev->call('newUser', $newUserParams);

        echo "<pre>";

        print_r($res);

    })->setName('nutshell-create-user');


});

$app->get('/', function($request, $response, $args) use ($app) {


    $entryData = array(
        'entrytype'     => 'contact',
        'recordid'      => 'fggfgf',
        'entrynutid'    => '11111'
    );

    //$app->db->Insert('entries', $entryData);

    echo "<pre>";

    print_r($entryData);


    // First test with api call

//     echo "<h4 style=\"text-align: center;\">Lets try to fetch some data from current nutshell account!</h4><br>";

//     $curParams = array(
//         'query'          => null,
//         'orderBy'        => 'id',
//         'orderDirection' => 'ASC',
//         'limit'          => 5,
//         'page'           => 1,
//         'stubResponses'  => true
//     );


//     $backupsParams = array();

//     //$res = $app->nutshellApi->call('findContacts', $curParams);
//     $res = $app->nutshellApiDev->call('findContacts', $curParams);

//     $contactCounter = 1;
//     ini_set('max_execution_time', 400);

//     foreach($res as $contactKey => $contact) {

//         $delContactParams = array(
//             'contactId' => $contact->id,
//             'rev'       => $contact->rev
//         );


//         echo "(" . $contactCounter++ . ") id : " . $contact->id . " rev : " . $contact->rev . "<br>";

//     }

})->setName('home');


$app->get('/client-accounts', function($request, $response, $args) use ($app) {

    echo "<h4 style=\"text-align: center;\">Test Client accounts structure.....</h4><br>";

    $curParams = array(

        'query'          => null,
        'orderBy'        => 'id',
        'orderDirection' => 'ASC',
        'limit'          => 100,
        'page'           => 36,
        'stubResponses'  => false

    );

    $res = $app->nutshellApiClient->call('findAccounts', $curParams);

    foreach($res as $accountKey => $account) {

        $accountParams = array(

            'accountId' => $account->id,
            'rev'       => null
        );

        $accountDet = $app->nutshellApiClient->call('getAccount', $accountParams);
        $accountDet = json_decode(json_encode($accountDet), true);

        echo "<pre>";
        print_r($accountDet);

    }

})->setName('client-accounts-data');

$app->get('/client-leads', function($request, $response, $args) use ($app) {

    echo "<h4 style=\"text-align: center;\">Lets try to fetch some data from current client nutshell account!</h4><br>";

    $curParams = array(
        'query'          => array(),
        'orderBy'        => 'id',
        'orderDirection' => 'ASC',
        'limit'          => 10,
        'page'           => 1,
        'stubResponses'  => true
    );

    ini_set('max_execution_time', 400);

    //$res = $app->nutshellApiClient->call('findLeads', $curParams);
    $res = $app->nutshellApiClient->call('findLeads', $curParams);
    foreach($res as $leadKey => $lead) {

        $leadParams = array(

            'leadId' => $lead->id,
            'rev'       => null

        );

        //$leadDet = $app->nutshellApiClient->call('getLead', $leadParams);
        $leadDet = $app->nutshellApiClient->call('getLead', $leadParams);
        echo "<pre>";
        print_r(json_decode(json_encode($leadDet), true));

    }

})->setName('client-leads-data');

$app->get('/client-contacts', function($request, $response, $args) use ($app) {

    echo "<h4 style=\"text-align: center;\">Lets try to fetch some data from current client nutshell account!</h4><br>";



    $curParams = array(
        'query'          => null,
        'orderBy'        => 'id',
        'orderDirection' => 'ASC',
        'limit'          => 10,
        'page'           => 1,
        'stubResponses'  => false
    );

    ini_set('max_execution_time', 400);

    $res = $app->nutshellApiClient->call('findContacts', $curParams);

    $res = json_decode(json_encode($res), true);

    foreach($res as $resKey => $r) {

        $contactDet = $app->nutshellApiClient->call('getContact', array('contactId'=>$r['id'], 'rev'=>null));

        $contactDet = json_decode(json_encode($contactDet), true);
        echo "<pre>";
        print_r($contactDet);
    }

})->setName('test-client-data');

$app->get('/client-products', function($request, $response, $args) use ($app) {

    $curParams = array(

        'orderBy'        => 'id',
        'orderDirection' => 'ASC',
        'limit'          => 100,
        'page'           => 1,
        'stubResponses'  => false

    );

    ini_set('max_execution_time', 400);

    $res = $app->nutshellApiClient->call('findProducts', $curParams);

    foreach($res as $productKey => $product) {

        $productParams = array(

            'productId'  => $product->id,
            'rev'        => null

        );

        $productDet = $app->nutshellApiClient->call('getProduct', $productParams);

        $productDet = (json_decode(json_encode($productDet), true));
        $newProductParams = array();
        foreach($productDet as $key => $val) {

            if($key == "name" || $key == "type" || $key == "sku" || $key == "unit") {

                $newProductParams['product'][$key] = $val;
            }

        }

        if(isset($newProductParams['product'])) {

            $createdProduct = $app->nutshellApiDev->call('newProduct', $newProductParams);
            print_r($createdProduct);
            echo "<br>";
        }
    }

})->setName('client-products');

$app->get('/client-processes', function($request, $response, $args) use ($app) {

    echo "processes";

})->setName('client-processes');

$app->get('/client-new-lead', function($request, $response, $args) use ($app) {

    $leadParams = array(
        'lead' => array(

            'milestoneId' => 2

        )
    );

    $newLead = $app->nutshellApiDev->call('newLead', $leadParams);

    echo "<pre>";
    print_r($newLead);

    $searchParam = array(

        'string'     => 'open'
    );

    $stages = $app->nutshellApiDev->call('searchUniversal', $searchParam);

    echo "<pre>";

    print_r($stages);

})->setName('client-new-lead');

$app->get('/client-new-note', function($request, $response, $args) use ($app) {

    $noteParams = array(

        'entity'      => array(

            'entityType'    => 'Contacts',
            'id'            => 59477
        ),

        'note'       => 'test note'

    );

    $createdNote = $app->nutshellApiDev->call('newNote', $noteParams);

    echo "<pre>";

    print_r($createdNote);


})->setName('client-new-note');

$app->get('/client-milestones', function($request, $response, $args) use ($app) {


    $mileStonesParams = array(

        'orderBy'          => 'name',
        'orderDirection'   => 'ASC',
        'limit'            => 50,
        'page'             => 1

    );

    $mileStones    = $app->nutshellApiDev->call('findMilestones', $mileStonesParams);

    echo "<pre>";

    print_r($mileStones);


})->setName('client-milestones');

$app->get('/client-markets', function($request, $response, $args) use ($app) {

    $curParams = array(

        'orderBy'         => 'id',
        'orderDirection'  => 'ASC',
        'limit'           => 100,
        'page'            => 1,
        'stubResponse'    => false
    );

    ini_set('max_execution_time', 400);

    $res = $app->nutshellApiClient->call('findMarkets', $curParams);

    foreach($res as $marketKey => $market) {

        print_r(json_decode(json_encode($market), true));
    }

})->setName('client-markets');

$app->get('/client-account-types', function($request, $response, $args) use ($app) {

    $curParams = array(

        'orderBy'         => 'id',
        'orderDirection'  => 'ASC',
        'limit'           => 100,
        'page'            => 1

    );

    ini_set('max_execution_time', 400);

    $res = $app->nutshellApiDev->call('findAccountTypes', $curParams);

    foreach($res as $accountTypesKey => $accountType) {

        print_r(json_decode(json_encode($accountType), true));

        echo "<br>";

    }

})->setName('client-account-types');

$app->get('/client-sources', function($request, $response, $args) use ($app) {

    $curParams = array(

        'orderBy'         => 'name',
        'orderDirection'  => 'ASC',
        'limit'           => 100,
        'page'            => 1

    );

    ini_set('max_execution_time', 40);

    $res = $app->nutshellApiClient->call('findSources', $curParams);

    foreach($res as $sourcesKey => $source) {

        $tempSource = json_decode(json_encode($source), true);

        $sourceParams = array(

            'name' => $tempSource['name']

        );

        $newSource = $app->nutshellApiDev->call('newSource', $sourceParams);

        print_r($newSource);
        echo "<br>";

    }

})->setName('client-sources');

$app->get('/client-tags', function($request, $response, $args) use ($app) {

    $curParams = array();

    ini_set('max_execution_time', 400);

    $res = $app->nutshellApiClient->call('findTags', $curParams);

    foreach($res as $tagsKey => $tag) {

        $tempTagsArray = json_decode(json_encode($tag), true);

        if(is_array($tempTagsArray)) {

            foreach($tempTagsArray as $key => $val) {

                $tagParams = array(

                    'tag'  => array(

                        'name'        => $val,
                        'entityType'  => $tagsKey
                    )
                );

                $newTag = $app->nutshellApiDev->call('newTag', $tagParams);

                print_r($newTag);
                echo "<br>";
            }
        }

    }

})->setName('client-tags');

$app->get('/client-users', function($request, $response, $args) use ($app) {

    $userParams = array(

        'query'               => null,
        'orderBy'             => 'id',
        'orderDirection'      => 'ASC',
        'limit'               =>  10,
        'page'                => 1

    );

    $users = $app->nutshellApiClient->call('findUsers', $userParams);

    $users = json_decode(json_encode($users), true);

    foreach($users as $userKey => $user) {

        $userDetails = $app->nutshellApiClient->call('getUser', array('userId'=>$user['id'], 'rev'=>null));

        $userDetails = json_decode(json_encode($userDetails), true);

        echo "<pre>";
        print_r($userDetails);

    }

})->setName('client-users');
