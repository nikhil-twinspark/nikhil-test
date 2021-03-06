<?php

/**
 * This file for rewards site for legacy pratice.
 * At the rewards site we have feature to patient login and get the access to modify there profile details,get the all transation details,redemption details,notification settings.
 * Patient can send referal message to his friends and family.
 * Patient can redeem local,amazon and product and service.
 * Add rewards to there wish list etc.
 */
App::uses('AppController', 'Controller');
App::import('Vendor', 'facebook/facebook');
App::uses('CakeEmail', 'Network/Email');

/**
 * This controller for rewards site for legacy pratice.
 * At the rewards site we have feature to patient login and get the access to modify there profile details,get the all transation details,redemption details,notification settings.
 * Patient can send referal message to his friends and family.
 * Patient can redeem local,amazon and product and service.
 * Add rewards to there wish list etc.
 */
class RewardsController extends AppController {

    /**
     * These all helper we use for this controller.
     * 
     * @var type 
     */
    public $helpers = array('Html', 'Form', 'Session');

    /**
     * We use the session, api,Security and CakeS3 component for this controller.
     * @var type 
     */
    public $components = array('Session', 'Api', 'Security', 'CakeS3.CakeS3' => array(
            's3Key' => AWS_KEY,
            's3Secret' => AWS_SECRET,
            'bucket' => AWS_BUCKET
    ));

    /**
     * These All database model in use.
     * @var type 
     */
    public $uses = array('Promotion', 'Reward', 'Staff', 'User', 'ClinicUser', 'ProfileFieldUser', 'Category', 'CardNumber', 'ProfileField', 'Document', 'IndustryType', 'Clinic', 'State', 'City', 'WishList', 'Transaction', 'Notification', 'Refer', 'UnregTransaction', 'ContestClinic', 'ClinicPromotion', 'Refpromotion', 'ProductService', 'LeadLevel', 'UsersBadge', 'Badge', 'AdminSetting', 'GlobalRedeem', 'Invoice', 'PaymentDetail', 'AccessStaff');

    /**
     * Useing the security validation and unlock other action for security check.
     * Checking the Patient credential is valid and then store all practice default value in session.
     * Checking if Patient already login then all data related to patient store in to session.
     * @return type
     */
    public function beforeFilter() {
        $this->Security->validatePost = false;
        //Unlock other action for security check.
        $this->Security->unlockedActions = array('getmultilogin', 'checkemailrefer', 'checkemail', 'getcity', 'getFacebookId', 'home', 'checkuserexist', 'linkwithcard', 'signup', 'facebooklogin', 'earn', 'refer', 'referpreview', 'getmsg', 'resendrefer', 'lead', 'redeemreward', 'editprofile', 'profile', 'forgotpassword', 'redeem', 'reward', 'productservice', 'redeemlocproduct', 'getreward', 'rewarddetail', 'addwishlist', 'contest', 'contestdetail', 'verifycard', 'dofootenquirysubmit', 'facebookpointallocation', 'stafflogin', 'selfcheckin', 'checkprofilecompletion', 'selfcheckinportal', 'selfcheckinportalques', 'patientlogout', 'load_file', 'getNextFreeCard', 'checkCardNumber');
        $this->Security->blackHoleCallback = 'blackhole';
        $iphone = strpos($_SERVER['HTTP_USER_AGENT'], "iPhone");
        $android = strpos($_SERVER['HTTP_USER_AGENT'], "Android");
        $palmpre = strpos($_SERVER['HTTP_USER_AGENT'], "webOS");
        $berry = strpos($_SERVER['HTTP_USER_AGENT'], "BlackBerry");
        $ipod = strpos($_SERVER['HTTP_USER_AGENT'], "iPod");
        //condition to check website open on mobile or web.
        if ($iphone || $android || $palmpre || $ipod || $berry == true) {
            $this->Session->write('patient.is_mobile', 1);
        } else {
            $this->Session->write('patient.is_mobile', 0);
        }
        $host = explode('.', $_SERVER['HTTP_HOST']);
        $options['conditions'] = array('Clinic.api_user' => $host[0]);
        $credResult = $this->Clinic->find('first', $options);
        //condition to check Practice exist with our system.
        if (empty($credResult)) {
            $options1['conditions'] = array('Clinic.patient_url Like' => "%" . $_SERVER['HTTP_HOST'] . "%");
            $credResult = $this->Clinic->find('first', $options1);
        }
        if (!empty($credResult)) {
            $this->Session->write('patient.ClinicDetails', $credResult['Clinic']);
            $staffaceess = $this->AccessStaff->getAccessForClinic($credResult['Clinic']['id']);
            //Getting Access for Pratice and store in to session.
            $this->Session->write('patient.staffaccess', $staffaceess);
            $options['conditions'] = array('ProductService.clinic_id' => $credResult['Clinic']['id'], 'ProductService.status' => 1);
            $ProductService = $this->ProductService->find('all', $options);
            //condition to check pratice have product and service.
            if (empty($ProductService)) {
                $this->Session->write('patient.product', 0);
            } else {
                $this->Session->write('patient.product', 1);
            }
            $this->Session->write('patient.clinic_id', $credResult['Clinic']['id']);
            $this->Session->write('patient.api_user', $credResult['Clinic']['api_user']);
            if ($credResult['Clinic']['is_buzzydoc'] == 1) {
                $isbuzzy = 1;
            } else {
                $isbuzzy = 0;
            }
            $this->Session->write('patient.is_lite', $credResult['Clinic']['is_lite']);
            $this->Session->write('patient.is_buzzydoc', $isbuzzy);
            //Store global and local profile fields in session.
            $ProField = $this->ProfileField->query('SELECT `ProfileField`.`id`, `ProfileField`.`profile_field`, `ProfileField`.`type`, `ProfileField`.`options`, `ProfileField`.`clinic_id` FROM `profile_fields` AS `ProfileField` WHERE ((`ProfileField`.`clinic_id` = 0) OR (`ProfileField`.`clinic_id` = ' . $credResult['Clinic']['id'] . ')) ');
            $this->Session->write('patient.ProfileField', $ProField);
            $ProFieldGlobal = $this->ProfileField->query('SELECT `ProfileField`.`id`, `ProfileField`.`profile_field`, `ProfileField`.`type`, `ProfileField`.`options`, `ProfileField`.`clinic_id` FROM `profile_fields` AS `ProfileField` WHERE `ProfileField`.`clinic_id` = 0 ');
            $this->Session->write('patient.ProFieldGlobal', $ProFieldGlobal);
            $this->Session->write('patient.Themes', $credResult['Clinic']);
            $contest = "SELECT * FROM contest_clinics as cc join contests as c on c.id=cc.contest_id WHERE cc.clinic_id=" . $credResult['Clinic']['id'];

            $challenges = $this->ContestClinic->query($contest);
            if (!empty($challenges)) {
                $cont = 1;
            } else {
                $cont = 0;
            }
            $this->Session->write('patient.Contest', $cont);
        }
        $sessionpatient = $this->Session->read('patient');
        if ($sessionpatient['is_lite'] == 1) {
            return $this->redirect(Buzzy_Name);
        }
        if (empty($sessionpatient['var']) && $this->params['action'] != 'selfcheckin' && $this->params['action'] != 'linkwithcard' && $this->params['action'] != 'checkuserexist' && $this->param['action'] != 'checkprofilecompletion' && $this->params['action'] != 'selfcheckinportal' && $this->params['action'] != 'selfcheckinportalques' && $this->params['action'] != 'stafflogout' && $this->params['action'] != 'stafflogin' && $this->params['action'] != 'login' && $this->params['action'] != 'lead' && $this->params['action'] != 'getmultilogin' && $this->params['action'] != 'checkemail' && $this->params['action'] != 'dofootenquirysubmit' && $this->params['action'] != 'getcity' && $this->params['action'] != 'redeemreward' && $this->params['action'] != 'facebooklogin' && $this->params['action'] != 'signup' && $this->params['action'] != 'forgotpassword' && $this->params['action'] != 'verifycard' && $this->params['action'] != 'resendrefer' && $this->params['action'] != 'getNextFreeCard' && $this->params['action'] != 'checkCardNumber') {
            $this->Session->delete('patient');
            return $this->redirect('/rewards/login/');
        } else if (!empty($sessionpatient['var'])) {
            //Condition to check Patient already login and store all details in session.

            $getdetail = $this->User->find('first', array(
                'joins' => array(
                    array(
                        'table' => 'clinic_users',
                        'alias' => 'clinic_users',
                        'type' => 'INNER',
                        'conditions' => array(
                            'clinic_users.user_id = User.id'
                        )
                    ),
                    array(
                        'table' => 'clinics',
                        'alias' => 'Clinic',
                        'type' => 'INNER',
                        'conditions' => array(
                            'Clinic.id = clinic_users.clinic_id'
                        )
                    )
                ),
                'conditions' => array(
                    'User.id' => $sessionpatient['customer_info']['user']['id'],
                    'clinic_users.clinic_id' => $sessionpatient['clinic_id']
                ),
                'fields' => array('clinic_users.*', 'User.*')
            ));
            $this->Session->write('patient.customer_info.user', $getdetail['User']);
            $this->Session->write('patient.customer_info.ClinicUser', $getdetail['clinic_users']);
            $rdquery = "SELECT * FROM rewards WHERE clinic_id=" . $sessionpatient['clinic_id'] . " and points!='' and description !='' UNION SELECT rewards.* from rewards inner join clinic_rewards on clinic_rewards.reward_id=rewards.id WHERE clinic_rewards.clinic_id=" . $sessionpatient['clinic_id'] . " and rewards.points!='' and rewards.description !=''  order by points";
            $query_rd = $this->Reward->query($rdquery);
            $n = 0;
            $rdar = array();
            foreach ($query_rd as $rd) {
                $rdar[$n]['Reward'] = $rd[0];
                $n++;
            }
            $this->Session->write('patient.Reward', $rdar);
            $transaction = $this->Transaction->find('all', array('conditions' => array('Transaction.user_id' => $sessionpatient['customer_info']['user']['id'])));
            $transaction_array = array();
            foreach ($transaction as $trans) {
                $transaction_array[] = $trans['Transaction'];
            }

            $this->Session->write('patient.customer_info.Transaction', $transaction_array);

            $pfield = $this->ProfileField->find('all', array(
                'joins' => array(
                    array(
                        'table' => 'profile_field_users',
                        'alias' => 'ProfileFieldUser',
                        'type' => 'INNER',
                        'conditions' => array(
                            'ProfileFieldUser.profilefield_id = ProfileField.id'
                        )
                    )
                ),
                'conditions' => array(
                    'ProfileFieldUser.user_id' => $sessionpatient['customer_info']['user']['id'],
                ),
                'fields' => array('ProfileField.*', 'ProfileFieldUser.*')
            ));
            $prflielddet = array();
            foreach ($pfield as $prffile) {

                if ($prffile['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $prffile['ProfileFieldUser']['clinic_id'] == 0) {

                    $prffile['ProfileField']['ProfileFieldUser'] = $prffile['ProfileFieldUser'];

                    $prflielddet[] = $prffile['ProfileField'];
                }
            }
            $this->Session->write('patient.customer_info.ProfileField', $prflielddet);
        }
    }

    /**
     * checking the any blackhole while login.
     * @param type $type
     * @return type
     */
    public function blackhole($type) {
        $this->log('Request has been blackholed: ' . $type, 'tests');
        $this->Session->setFlash(__('Looks like you attempted to pass that request incorrectly. 
Please refresh the page and try again.'));
        return $this->redirect(array('controller' => 'rewards', 'action' => 'login'));
    }

    /**
     * Function to login from rewards site and direct login from buzzydoc and super admin site.
     * @return type
     */
    public function login() {

        $this->layout = "patientLayoutLogin";
        $sessionpatient = $this->Session->read('patient');
        //Getting the list of all Documents.
        if ($sessionpatient['staffaccess']['AccessStaff']['show_documents'] == 1) {
            $options1['conditions'] = array('Document.clinic_id' => $sessionpatient['clinic_id']);
            $options1['order'] = array('Document.title' => 'desc');
            $Documents = $this->Document->find('all', $options1);
        } else {
            $Documents = array();
        }
        $this->set('Documents', $Documents);
        //condition to check direct login from super admin and buzzydoc.
        if (isset($this->request->pass[0]) && isset($this->request->pass[1]) && isset($this->request->pass[2])) {

            if (isset($sessionpatient['clinic_id'])) {
                if (base64_decode($this->request->pass[1]) != '') {
                    $Pcheck = $this->User->find('first', array(
                        'joins' => array(
                            array(
                                'table' => 'clinic_users',
                                'alias' => 'clinic_users',
                                'type' => 'INNER',
                                'conditions' => array(
                                    'clinic_users.user_id = User.id'
                                )
                            ),
                            array(
                                'table' => 'clinics',
                                'alias' => 'Clinic',
                                'type' => 'INNER',
                                'conditions' => array(
                                    'Clinic.id = clinic_users.clinic_id'
                                )
                            )
                        ),
                        'conditions' => array(
                            'clinic_users.card_number' => base64_decode($this->request->pass[1]),
                            'User.id' => base64_decode($this->request->pass[2]),
                            'clinic_users.clinic_id' => $sessionpatient['clinic_id'],
                            'User.blocked !=' => 1
                        ),
                        'fields' => array('clinic_users.*', 'User.*')
                    ));
                    //If patient exist with practice.
                    if (!empty($Pcheck)) {
                        $date1 = $Pcheck['User']['custom_date'];
                        $date2 = date('Y-m-d');
                        $diff = abs(strtotime($date2) - strtotime($date1));
                        $years = floor($diff / (365 * 60 * 60 * 24));
                        //Condition for parent account
                        if ($years < 18) {
                            $Patients_get = $this->User->find('first', array(
                                'joins' => array(
                                    array(
                                        'table' => 'clinic_users',
                                        'alias' => 'ClinicUser',
                                        'type' => 'INNER',
                                        'conditions' => array(
                                            'ClinicUser.user_id = User.id'
                                        )
                                    )
                                ),
                                'conditions' => array(
                                    'ClinicUser.card_number' => base64_decode($this->request->pass[1]),
                                    'User.id' => base64_decode($this->request->pass[2]),
                                    'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                                    'User.blocked !=' => 1
                                ),
                                'fields' => array('User.*')
                            ));
                        } else {
                            //check that parent have any child
                            $Patients_getchild = $this->ClinicUser->find('all', array(
                                'joins' => array(
                                    array(
                                        'table' => 'users',
                                        'alias' => 'user',
                                        'type' => 'INNER',
                                        'conditions' => array(
                                            'user.id = ClinicUser.user_id'
                                        )
                                    )),
                                'conditions' => array(
                                    'user.email' => $Pcheck['User']['email'],
                                    'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                                    'ClinicUser.user_id !=' => $Pcheck['clinic_users']['user_id'],
                                    'user.status' => 1
                                ),
                                'fields' => array('user.*', 'ClinicUser.*')
                            ));
                            if (count($Patients_getchild) > 0) {
                                $cnt = 0;
                                $child_detail = array();
                                foreach ($Patients_getchild as $child) {
                                    $date1_chd = $child['user']['custom_date'];
                                    $date2_chd = date('Y-m-d');
                                    $diff_chd = abs(strtotime($date2_chd) - strtotime($date1_chd));
                                    $years_chd = floor($diff_chd / (365 * 60 * 60 * 24));
                                    if ($years_chd < 18) {
                                        $child_detail[] = $child;
                                        $cnt++;
                                    }
                                }
                                if ($cnt > 0) {
                                    $this->Session->write('patient.is_parent', 'true');
                                    $this->Session->write('patient.child_detail', $child_detail);
                                }
                            }
                        }
                        $this->loadModel('user');
                        $Patients = $this->user->find('first', array('conditions' => array('user.id' => $Pcheck['clinic_users']['user_id'])));
                        //condition to check child account have verified.
                        if ($Patients['user']['is_verified'] == 1) {

                            foreach ($Patients['Clinic'] as $clinic) {
                                if ($clinic['id'] == $sessionpatient['clinic_id']) {
                                    $userglobalval = $clinic['ClinicUser'];
                                }
                            }
                            $pfieldarray = array();
                            foreach ($Patients['ProfileField'] as $ProfileField) {
                                if ($ProfileField['profile_field'] == 'gender') {
                                    $ProfileFieldval = $ProfileField['ProfileFieldUser']['value'];
                                }
                                if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                                    $pfieldarray[] = $ProfileField;
                                }
                            }

                            $Patients['ProfileField'] = $pfieldarray;
                            if ($sessionpatient['is_buzzydoc'] == 1) {
                                $networkclinic = array();
                                foreach ($Patients['Clinic'] as $allclinic) {
                                    if ($allclinic['is_buzzydoc'] == 1) {
                                        $networkclinic[] = $allclinic['id'];
                                    }
                                }

                                $this->Session->write('patient.networkclinic', $networkclinic);
                            }
                            $Reward = $this->Reward->find('all', array('conditions' => array('Reward.clinic_id' => $sessionpatient['clinic_id'], 'Reward.points !=' => '', 'Reward.description !=' => '')));
                            $this->Session->write('patient.Reward', $Reward);
                            $this->Session->write('patient.var.patient_name', $Pcheck['clinic_users']['card_number']);
                            $this->Session->write('patient.var.patient_password', $Pcheck['User']['password']);
                            $this->Session->write('patient.customer_info', $Patients);
                            $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                            if ($sessionpatient['is_buzzydoc'] == 1) {
                                //checking condition for patient apply for unsubscribe from email notification.
                                if (isset($this->request->pass[3]) && $this->request->pass[3] == 'Unsubscribe') {
                                    return $this->redirect(Buzzy_Name . 'buzzydoc/login/' . base64_encode($Patients['user']['id']) . '/Unsubscribe');
                                } else {
                                    return $this->redirect(Buzzy_Name . 'buzzydoc/login/' . base64_encode($Patients['user']['id']));
                                }
                            } else {
                                if (isset($this->request->pass[3]) && $this->request->pass[3] == 'Unsubscribe') {
                                    return $this->redirect('/rewards/profile/#linotification');
                                } else {
                                    if ($Patients['user']['first_name'] == '' || $Patients['user']['last_name'] == '' || $Patients['user']['email'] == '' || $ProfileFieldval == '' || $Patients['user']['custom_date'] == '') {
                                        $this->Session->setFlash(__('Please fill in the mandatory fields before proceeding'));
                                        return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                                    } else {
                                        if ($sessionpatient['is_buzzydoc'] == 1) {
                                            return $this->redirect(array('controller' => 'rewards', 'action' => 'home'));
                                        } else {

                                            return $this->redirect(array('controller' => 'rewards', 'action' => 'reward'));
                                        }
                                    }
                                }
                            }
                        } else {
                            $this->Session->setFlash(__("Waiting on parent's email confirmation."));
                        }
                    } else {
                        //Condition to check account blocked.
                        $Pcheck_block = $this->ClinicUser->find('first', array(
                            'joins' => array(
                                array(
                                    'table' => 'users',
                                    'alias' => 'user',
                                    'type' => 'INNER',
                                    'conditions' => array(
                                        'user.id = ClinicUser.user_id'
                                    )
                                )),
                            'conditions' => array(
                                'ClinicUser.card_number' => base64_decode($this->request->pass[1]),
                                'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                                'user.id' => base64_decode($this->request->pass[2]),
                                'user.blocked' => 1,
                            ),
                            'fields' => array('ClinicUser.*')
                        ));

                        if (!empty($Pcheck_block)) {
                            $this->Session->setFlash(__('Your Account has been blocked.Please contact to clinic admin.'));
                        } else {
                            $this->Session->setFlash(__('Invalid credentials'));
                        }
                    }
                    $this->Session->delete('patient.var');
                } else {
                    $this->Session->setFlash(__('Invalid credentials'));
                }
            } else {
                $this->Session->setFlash(__('Clinic does not exists.'));
            }
        }
        //condition to check request for verify child account.
        if (isset($this->request->pass[0]) && isset($this->request->pass[1]) && !isset($this->request->pass[2])) {
            $Patients_get = $this->ClinicUser->find('first', array(
                'joins' => array(
                    array(
                        'table' => 'users',
                        'alias' => 'user',
                        'type' => 'INNER',
                        'conditions' => array(
                            'user.id = ClinicUser.user_id'
                        )
                    )),
                'conditions' => array(
                    'ClinicUser.clinic_id' => base64_decode($this->request->pass[0]),
                    'ClinicUser.user_id' => base64_decode($this->request->pass[1]),
                ),
                'fields' => array('ClinicUser.*', 'user.*')
            ));

            if (!empty($Patients_get) && ($Patients_get['user']['is_verified'] == '' || $Patients_get['user']['is_verified'] == 0)) {
                $template_array = $this->Api->get_template(4);
                $link = str_replace('[first_name]', $Patients_get['user']['first_name'], $template_array['content']);
                $link1 = str_replace('[username]', $Patients_get['ClinicUser']['card_number'], $link);
                $link2 = str_replace('[password]', $Patients_get['user']['password'], $link1);
                $link3 = str_replace('[click_here]', '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . '">Click Here</a>', $link2);
                $sub = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $template_array['subject']);
                $Email = new CakeEmail(MAILTYPE);
                $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
                $Email->to($Patients_get['user']['email']);
                $Email->subject($sub)
                        ->template('buzzydocother')
                        ->emailFormat('html');
                $Email->viewVars(array('msg' => $link3
                ));
                $Email->send();

                $sessionpatient = $this->Session->read('patient');
                $Patients_array['User'] = array('id' => $Patients_get['ClinicUser']['user_id'], 'status' => 1, 'is_verified' => 1);
                $this->User->save($Patients_array);
                $this->Session->setFlash(__('Confirmation successful'));
            } else {
                $this->Session->setFlash(__('Already Confirmed.'));
            }
        }
        //Login from rewards site.
        if ($this->request->is('post')) {
            if (isset($sessionpatient['clinic_id'])) {
                //Condition to check child login.
                if ($this->request->data['login']['patient_name'] != '') {
                    $Pcheck = $this->User->find('first', array(
                        'joins' => array(
                            array(
                                'table' => 'clinic_users',
                                'alias' => 'clinic_users',
                                'type' => 'INNER',
                                'conditions' => array(
                                    'clinic_users.user_id = User.id'
                                )
                            ),
                            array(
                                'table' => 'clinics',
                                'alias' => 'Clinic',
                                'type' => 'INNER',
                                'conditions' => array(
                                    'Clinic.id = clinic_users.clinic_id'
                                )
                            )
                        ),
                        'conditions' => array(
                            'OR' => array('clinic_users.card_number' => $this->request->data['login']['patient_name'],
                                'User.email' => $this->request->data['login']['patient_name'],
                                'User.parents_email' => $this->request->data['login']['patient_name']),
                            'clinic_users.clinic_id' => $sessionpatient['clinic_id'],
                            'BINARY (`User`.`customer_password`) LIKE' => md5($this->request->data['login']['patient_password']),
                            'User.blocked !=' => 1,
                            'clinic_users.card_number !=' => '',
                            'User.customer_password !=' => ''
                        ),
                        'fields' => array('clinic_users.*', 'User.*')
                    ));
                    if (!empty($Pcheck)) {
                        $date1 = $Pcheck['User']['custom_date'];
                        $date2 = date('Y-m-d');
                        $diff = abs(strtotime($date2) - strtotime($date1));
                        $years = floor($diff / (365 * 60 * 60 * 24));
                        if ($years < 18) {
                            $Patients_get = $this->User->find('first', array(
                                'joins' => array(
                                    array(
                                        'table' => 'clinic_users',
                                        'alias' => 'ClinicUser',
                                        'type' => 'INNER',
                                        'conditions' => array(
                                            'ClinicUser.user_id = User.id'
                                        )
                                    )
                                ),
                                'conditions' => array(
                                    'OR' => array('ClinicUser.card_number' => $this->request->data['login']['patient_name'],
                                        'User.email' => $this->request->data['login']['patient_name'],
                                        'User.parents_email' => $this->request->data['login']['patient_name']),
                                    'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                                    'BINARY (`User`.`customer_password`) LIKE' => md5($this->request->data['login']['patient_password']),
                                ),
                                'fields' => array('User.*')
                            ));
                        } else {
                            //check that parent have any child
                            $Patients_getchild = $this->ClinicUser->find('all', array(
                                'joins' => array(
                                    array(
                                        'table' => 'users',
                                        'alias' => 'user',
                                        'type' => 'INNER',
                                        'conditions' => array(
                                            'user.id = ClinicUser.user_id'
                                        )
                                    )),
                                'conditions' => array(
                                    'user.email' => $Pcheck['User']['email'],
                                    'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                                    'ClinicUser.user_id !=' => $Pcheck['clinic_users']['user_id'],
                                    'user.status' => 1
                                ),
                                'fields' => array('user.*', 'ClinicUser.*')
                            ));
                            if (count($Patients_getchild) > 0) {
                                $cnt = 0;
                                $child_detail = array();
                                foreach ($Patients_getchild as $child) {
                                    $date1_chd = $child['user']['custom_date'];
                                    $date2_chd = date('Y-m-d');
                                    $diff_chd = abs(strtotime($date2_chd) - strtotime($date1_chd));
                                    $years_chd = floor($diff_chd / (365 * 60 * 60 * 24));
                                    if ($years_chd < 18) {
                                        $child_detail[] = $child;
                                        $cnt++;
                                    }
                                }
                                if ($cnt > 0) {
                                    $this->Session->write('patient.is_parent', 'true');
                                    $this->Session->write('patient.child_detail', $child_detail);
                                }
                            }
                        }
                        $this->loadModel('user');
                        $Patients = $this->user->find('first', array('conditions' => array('user.id' => $Pcheck['clinic_users']['user_id'])));

                        if ($Patients['user']['is_verified'] == 1) {

                            foreach ($Patients['Clinic'] as $clinic) {
                                if ($clinic['id'] == $sessionpatient['clinic_id']) {
                                    $userglobalval = $clinic['ClinicUser'];
                                }
                            }
                            $pfieldarray = array();
                            foreach ($Patients['ProfileField'] as $ProfileField) {
                                if ($ProfileField['profile_field'] == 'gender') {
                                    $ProfileFieldval = $ProfileField['ProfileFieldUser']['value'];
                                }
                                if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                                    $pfieldarray[] = $ProfileField;
                                }
                            }

                            $Patients['ProfileField'] = $pfieldarray;
                            if ($sessionpatient['is_buzzydoc'] == 1) {
                                $networkclinic = array();
                                foreach ($Patients['Clinic'] as $allclinic) {
                                    if ($allclinic['is_buzzydoc'] == 1) {
                                        $networkclinic[] = $allclinic['id'];
                                    }
                                }

                                $this->Session->write('patient.networkclinic', $networkclinic);
                            }
                            $Reward = $this->Reward->find('all', array('conditions' => array('Reward.clinic_id' => $sessionpatient['clinic_id'], 'Reward.points !=' => '', 'Reward.description !=' => '')));
                            $this->Session->write('patient.Reward', $Reward);
                            $this->Session->write('patient.var.patient_name', $Pcheck['clinic_users']['card_number']);
                            $this->Session->write('patient.var.patient_password', $Pcheck['User']['password']);
                            $this->Session->write('patient.customer_info', $Patients);
                            $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                            $usersbg = $this->Transaction->query('SELECT sum(Transaction.amount) as share FROM `transactions` AS `Transaction` where  Transaction.activity_type="N" and Transaction.user_id=' . $Pcheck['User']['id']);
                            foreach ($usersbg as $ug) {
                                if ($ug[0]['share'] > 0) {
                                    $optionsbadge['conditions'] = array('Badge.value <=' => $ug[0]['share'], 'Badge.clinic_id' => 0);
                                    $Badge = $this->Badge->find('all', $optionsbadge);
                                    foreach ($Badge as $bg) {
                                        $optionsbadgeuser['conditions'] = array('UsersBadge.user_id' => $Pcheck['User']['id'], 'UsersBadge.badge_id' => $bg['Badge']['id']);
                                        $Badgeuser = $this->UsersBadge->find('first', $optionsbadgeuser);
                                        if (empty($Badgeuser)) {
                                            $savebadge['UsersBadge'] = array(
                                                'user_id' => $Pcheck['User']['id'],
                                                'badge_id' => $bg['Badge']['id'],
                                                'created_on' => date('Y-m-d H:i:s')
                                            );
                                            $this->UsersBadge->create();
                                            $this->UsersBadge->save($savebadge);
                                        }
                                    }
                                }
                            }
                            if (isset($this->request->data['login']['selfcheckin']) && $this->request->data['login']['selfcheckin'] == 1) {
                                $this->Session->write('patient.selfcheckin.var.patient_name', $Pcheck['clinic_users']['card_number']);
                                $this->Session->write('patient.selfcheckin.var.patient_password', $Pcheck['User']['password']);
                                $this->Session->write('patient.selfcheckin.var.patient_id', $Pcheck['User']['id']);
                                $this->Session->write('patient.selfcheckin.var.last_log', $Pcheck['User']['selfcheckin_log']);
                                return $this->redirect(array('controller' => 'rewards', 'action' => 'checkprofilecompletion'));
                            } else {
                                if ($sessionpatient['is_buzzydoc'] == 1) {
                                    return $this->redirect(Buzzy_Name . 'buzzydoc/login/' . base64_encode($Patients['user']['id']));
                                } else {
                                    if ($Patients['user']['first_name'] == '' || $Patients['user']['last_name'] == '' || $Patients['user']['email'] == '' || $ProfileFieldval == '' || $Patients['user']['custom_date'] == '') {
                                        $this->Session->setFlash(__('Please fill in the mandatory fields before proceeding'));
                                        return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                                    } else {
                                        return $this->redirect(array('controller' => 'rewards', 'action' => 'home'));
                                    }
                                }
                            }
                        } else {
                            $this->Session->setFlash(__("Waiting on parent's email confirmation."));
                            if (isset($this->request->data['login']['selfcheckin']) && $this->request->data['login']['selfcheckin'] == 1) {
                                return $this->redirect(array('controller' => 'rewards', 'action' => 'selfcheckin'));
                            }
                        }
                    } else {
                        $Pcheck_block = $this->ClinicUser->find('first', array(
                            'joins' => array(
                                array(
                                    'table' => 'users',
                                    'alias' => 'user',
                                    'type' => 'INNER',
                                    'conditions' => array(
                                        'user.id = ClinicUser.user_id'
                                    )
                                )),
                            'conditions' => array(
                                'OR' => array('ClinicUser.card_number' => $this->request->data['login']['patient_name'],
                                    'user.email' => $this->request->data['login']['patient_name'],
                                    'user.parents_email' => $this->request->data['login']['patient_name']),
                                'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                                'user.customer_password' => md5($this->request->data['login']['patient_password']),
                                'user.blocked' => 1,
                                'ClinicUser.card_number !=' => '',
                                'user.customer_password !=' => ''
                            ),
                            'fields' => array('ClinicUser.*')
                        ));

                        if (!empty($Pcheck_block)) {
                            $this->Session->setFlash(__('Your Account has been blocked.Please contact to clinic admin.'));
                            if (isset($this->request->data['login']['selfcheckin']) && $this->request->data['login']['selfcheckin'] == 1) {
                                return $this->redirect(array('controller' => 'rewards', 'action' => 'selfcheckin'));
                            }
                        } else {
                            $this->Session->setFlash(__('Invalid credentials'));
                            if (isset($this->request->data['login']['selfcheckin']) && $this->request->data['login']['selfcheckin'] == 1) {
                                return $this->redirect(array('controller' => 'rewards', 'action' => 'selfcheckin'));
                            }
                        }
                    }
                    $this->Session->delete('patient.var');
                } else {
                    $this->Session->setFlash(__('Invalid credentials'));
                }
            } else {
                $this->Session->setFlash(__('Clinic does not exists.'));
            }
        }

        $state = $this->State->find('all');
        $this->set('states', $state);
    }

    /**
     * Function for if Parent account have child then option to login at child account.
     * @return type
     */
    public function getmultilogin() {
        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');

        if (isset($this->request->data['parent_login'])) {
            $this->Session->delete('patient.var');
            $this->Session->delete('patient.is_parent');
            $this->Session->write('patient.parent_login', 'true');
            $this->Session->write('patient.parent_id', $this->request->data['parent_id']);
        }
        if (isset($this->request->data['parent_back'])) {
            $this->Session->delete('patient.var');
            $this->Session->delete('patient.parent_login');
            $this->Session->delete('patient.parent_id');
            $this->Session->write('patient.is_parent', 'true');
        }
        $Pcheck = $this->User->find('first', array(
            'joins' => array(
                array(
                    'table' => 'clinic_users',
                    'alias' => 'clinic_users',
                    'type' => 'INNER',
                    'conditions' => array(
                        'clinic_users.user_id = User.id'
                    )
                ),
                array(
                    'table' => 'clinics',
                    'alias' => 'Clinic',
                    'type' => 'INNER',
                    'conditions' => array(
                        'Clinic.id = clinic_users.clinic_id'
                    )
                )
            ),
            'conditions' => array(
                'User.id' => $this->request->data['child_id'],
                'User.blocked !=' => 1
            ),
            'fields' => array('clinic_users.*', 'User.*')
        ));
        if (isset($Pcheck) && !empty($Pcheck)) {
            if (isset($sessionpatient['api_user'])) {
                $this->loadModel('user');
                $Patients = $this->user->find('first', array('conditions' => array('user.id' => $Pcheck['clinic_users']['user_id'])));
                if ($Patients['user']['is_verified'] == 1) {

                    foreach ($Patients['Clinic'] as $clinic) {
                        if ($clinic['id'] == $sessionpatient['clinic_id']) {
                            $userglobalval = $clinic['ClinicUser'];
                        }
                    }
                    $pfieldarray = array();
                    foreach ($Patients['ProfileField'] as $ProfileField) {
                        if ($ProfileField['profile_field'] == 'gender') {
                            $ProfileFieldval = $ProfileField['ProfileFieldUser']['value'];
                        }
                        if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                            $pfieldarray[] = $ProfileField;
                        }
                    }
                    $Patients['ProfileField'] = $pfieldarray;
                    $Reward = $this->Reward->find('all', array('conditions' => array('Reward.clinic_id' => $sessionpatient['clinic_id'], 'Reward.points !=' => '', 'Reward.description !=' => '')));
                    if ($Patients['user']['first_name'] == '' || $Patients['user']['last_name'] == '' || $Patients['user']['email'] == '' || $ProfileFieldval == '' || $Patients['user']['custom_date'] == '') {

                        $this->Session->write('patient.Reward', $Reward);
                        $this->Session->write('patient.var.patient_name', $Pcheck['clinic_users']['card_number']);
                        $this->Session->write('patient.var.patient_password', $Pcheck['User']['password']);
                        $this->Session->write('patient.customer_info', $Patients);
                        $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                        $this->Session->setFlash(__('Please fill in the mandatory fields before proceeding'));
                        return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                    } else {
                        $this->Session->write('patient.Reward', $Reward);
                        $this->Session->write('patient.var.patient_name', $Pcheck['clinic_users']['card_number']);
                        $this->Session->write('patient.var.patient_password', $Pcheck['User']['password']);
                        $this->Session->write('patient.customer_info', $Patients);
                        $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                        return $this->redirect(array('controller' => 'rewards', 'action' => 'home'));
                    }
                } else {
                    $this->Session->setFlash(__("Waiting on parent's email confirmation."));
                }
            } else {
                $this->Session->setFlash(__('Clinic does not exists.'));
            }
        } else {
            $Pcheck_block = $this->User->find('first', array(
                'conditions' => array(
                    'user.id' => $this->request->data['child_id'],
                    'user.blocked' => 1
                )
            ));
            if (!empty($Pcheck_block)) {
                $this->Session->setFlash(__('Your Account has been blocked.Please contact to clinic admin'));
            } else {
                $this->Session->setFlash(__('Invalid Credentails'));
            }
        }

        exit;
    }

    /**
     * Function to check email is already refered by patient.
     */
    public function checkemailrefer() {
        $this->layout = "";
        $users_field = $this->User->find('first', array('conditions' => array('OR' => array('User.email' => $_POST['email'], 'User.parents_email' => $_POST['email']))));
        if (empty($users_field)) {
            echo 0;
        } else {
            echo 1;
        }
        exit;
    }

    /**
     * Function to check email or username is unique or not.
     */
    public function checkemail() {
        $this->layout = "";
        if (isset($_POST['user_id'])) {
            $users_field = $this->User->find('all', array('conditions' => array('User.id !=' => $_POST['user_id'], 'OR' => array('User.email !=' => '', 'User.parents_email !=' => ''))));
        } else {
            $users_field = $this->User->find('all', array('conditions' => array('OR' => array('User.email !=' => '', 'User.parents_email !=' => ''))));
        }

        if (isset($_POST['email']) && isset($_POST['parents_email'])) {

            if (isset($_POST['parents_email']) && $_POST['parents_email'] != '' && $_POST['email'] != '') {

                foreach ($users_field as $user) {
                    if ($user['User']['email'] == $_POST['email'] && $user['User']['parents_email'] == $_POST['parents_email']) {
                        $check = 1;
                        break;
                    } else {
                        $check = 0;
                    }
                }

                foreach ($users_field as $user) {
                    if ($user['User']['parents_email'] == $_POST['email']) {
                        $check1 = 1;
                        break;
                    } else {
                        $check1 = 0;
                    }
                }
                foreach ($users_field as $user) {
                    if ($user['User']['email'] != $_POST['email'] && $user['User']['parents_email'] == $_POST['parents_email'] && $user['User']['parents_email'] != '') {
                        $check3 = 1;
                        break;
                    } else {
                        $check3 = 0;
                    }
                }

                foreach ($users_field as $user) {
                    if ($user['User']['email'] == $_POST['parents_email']) {
                        $check2 = 1;
                        break;
                    } else {
                        $check2 = 0;
                    }
                }

                if ($check == 1) {
                    echo 1;
                } else if ($check1 == 1) {
                    echo 2;
                } else if ($check2 == 1) {
                    echo 4;
                } else if ($check3 == 1) {
                    echo 4;
                } else {
                    echo 0;
                }
            } else if (isset($_POST['parents_email']) && $_POST['parents_email'] == '' && $_POST['email'] != '') {

                foreach ($users_field as $user) {
                    if ($user['User']['parents_email'] == $_POST['email']) {
                        $check2 = 1;
                        break;
                    } else {
                        $check2 = 0;
                    }
                }
                if ($check2 == 1) {
                    echo 2;
                } else {
                    echo 0;
                }
            } else if (isset($_POST['parents_email']) && $_POST['parents_email'] != '' && $_POST['email'] == '') {

                foreach ($users_field as $user) {
                    if ($user['User']['email'] == $_POST['parents_email']) {
                        $check2 = 1;
                        break;
                    } else {
                        $check2 = 0;
                    }
                }
                foreach ($users_field as $user) {
                    if ($user['User']['parents_email'] == $_POST['parents_email']) {
                        $check1 = 1;
                        break;
                    } else {
                        $check1 = 0;
                    }
                }


                if ($check2 == 1 || $check1 == 1) {
                    echo 4;
                } else {
                    echo 0;
                }
            } else {
                echo 0;
            }
        } else {
            if ($_POST['email'] != '') {
                $date13age = date("Y-m-d", strtotime("-18 year"));
                foreach ($users_field as $user) {
                    if ($user['User']['parents_email'] == $_POST['email']) {
                        $check2 = 1;
                        break;
                    } else {
                        $check2 = 0;
                    }
                }

                foreach ($users_field as $user) {
                    if ($user['User']['email'] == $_POST['email'] && $user['User']['parents_email'] == '' && $user['User']['custom_date'] < $date13age) {
                        $check1 = 1;
                        break;
                    } else {
                        $check1 = 0;
                    }
                }

                if ($check1 == 1) {
                    echo 3;
                } else if ($check2 == 1) {
                    echo 2;
                } else {
                    echo 0;
                }
            } else {
                echo 0;
            }
        }


        exit;
    }

    /**
     * getting the city list dropdown for state.
     */
    public function getcity() {
        $this->layout = "";
        $options['joins'] = array(
            array('table' => 'states',
                'alias' => 'States',
                'type' => 'INNER',
                'conditions' => array(
                    'States.state_code = City.state_code',
                    'States.state = "' . $_POST['state'] . '"'
                )
            )
        );
        $options['fields'] = array('City.city');
        $options['order'] = array('City.city asc');
        $cityresult = $this->City->find('all', $options);
        $html = '<option value="">Select City</option>';
        foreach ($cityresult as $ct) {
            $html .='<option value="';
            $html .=$ct["City"]["city"];
            $html .='">';
            $html .=$ct["City"]["city"];
            $html .='</option>';
        }
        echo $html;
        exit;
    }

    /**
     * Getting the facebook page id by given url.
     * @param type $url
     * @return type
     */
    public function getFacebookId($url) {
        $id = substr(strrchr($url, '/'), 1);
        $json = file_get_contents('http://graph.facebook.com/' . $id);
        $json = json_decode($json);
        return $json->id;
    }

    /**
     * Landing page for patient after login and view all details related to patient account.
     */
    public function home() {

        /*         * **************************facebook like check********************** */
        $this->layout = "patientLayout";
        $this->set('errorMsg', "");
        $sessionpatient = $this->Session->read('patient');
        //transafer transaction from unreg to register
        $alltrans = $this->UnregTransaction->find('all', array(
            'conditions' => array(
                'UnregTransaction.user_id' => 0,
                'UnregTransaction.card_number' => $sessionpatient['customer_info']['ClinicUser']['card_number'],
                'UnregTransaction.clinic_id' => $sessionpatient['clinic_id']
            )
        ));

        foreach ($alltrans as $newtran) {
            $datatrans['user_id'] = $sessionpatient['customer_info']['user']['id'];
            $datatrans['staff_id'] = $newtran['UnregTransaction']['staff_id'];
            $datatrans['card_number'] = $sessionpatient['customer_info']['ClinicUser']['card_number'];
            $datatrans['first_name'] = $sessionpatient['customer_info']['user']['first_name'];
            $datatrans['last_name'] = $sessionpatient['customer_info']['user']['last_name'];
            $datatrans['promotion_id'] = $newtran['UnregTransaction']['promotion_id'];
            $datatrans['amount'] = $newtran['UnregTransaction']['amount'];
            $datatrans['activity_type'] = $newtran['UnregTransaction']['activity_type'];
            $datatrans['authorization'] = $newtran['UnregTransaction']['authorization'];
            $datatrans['clinic_id'] = $newtran['UnregTransaction']['clinic_id'];
            $datatrans['date'] = $newtran['UnregTransaction']['date'];
            $datatrans['status'] = $newtran['UnregTransaction']['status'];
            $datatrans['is_buzzydoc'] = 0;
            $this->Transaction->create();
            $this->Transaction->save($datatrans);
            $this->UnregTransaction->deleteAll(array('UnregTransaction.id' => $newtran['UnregTransaction']['id'], false));
        }
        if (!empty($alltrans)) {

            $allpoints = $this->Transaction->find('first', array(
                'conditions' => array(
                    'Transaction.user_id' => $sessionpatient['customer_info']['user']['id'],
                    'Transaction.clinic_id' => $sessionpatient['clinic_id'],
                ),
                'fields' => array(
                    'SUM(Transaction.amount) AS points'
                ),
                'group' => array(
                    'Transaction.card_number'
            )));

            $newpoints = $allpoints[0]['points'];

            $this->ClinicUser->query("update clinic_users set local_points=" . $newpoints . " where user_id=" . $sessionpatient['customer_info']['user']['id'] . " and clinic_id=" . $sessionpatient['clinic_id']);

            $this->Session->write('patient.customer_info.ClinicUser.local_points', $newpoints);
        }
        //end code
        try {
            //condition to check patient already like the facebook page for pratice if yes then assign the facbook like points to account.
            if (isset($sessionpatient['customer_info']) && isset($sessionpatient['Themes']['fb_app_id'])) {
                $this->set("loginUrl", '');
                $Patients1 = array();
                $patients_api_user = $sessionpatient['api_user'];
                $patients_card_number = $sessionpatient['customer_info']['ClinicUser']['card_number'];
                $patients_email = $sessionpatient['customer_info']['user']['email'];
                $patients_id = $sessionpatient['customer_info']['user']['id'];
                $config = array(
                    'appId' => $sessionpatient['Themes']['fb_app_id'],
                    'secret' => $sessionpatient['Themes']['fb_app_key'],
                    'allowSignedRequest' => false
                );
                $facebook_url = $sessionpatient['Themes']['facebook_url'];
                $facebook = new Facebook($config);
                $user = $facebook->getUser();
                if ($facebook_url != '') {
                    $page_id = $this->getFacebookId($facebook_url);

                    if ($user) {


                        $user_fb_email = '';
                        $user_profile = $facebook->api('/me');
                        if (array_key_exists("email", $user_profile)) {
                            $user_fb_email = $user_profile['email'];
                        }

                        $qry = "SELECT page_id FROM page_fan WHERE page_id = '" . $page_id . "' AND uid = '" . $user . "'";
                        $isFan = $facebook->api(array(
                            "method" => "fql.query",
                            "query" => $qry
                        ));
                        //page id=139168709464290
                        //first like

                        if ((count($isFan) > 0) && ($sessionpatient['customer_info']['ClinicUser']['facebook_like_status'] == '' || $sessionpatient['customer_info']['ClinicUser']['facebook_like_status'] == 0)) { //first like
                            $this->set("loginUrl", "");

                            $options_pro['fields'] = array('Promotion.id', 'Promotion.value', 'Promotion.description', 'Promotion.operand');
                            $options_pro['conditions'] = array('Promotion.clinic_id' => $sessionpatient['clinic_id'], 'Promotion.description like' => '%Facebook Like%');

                            $Promotions = $this->Promotion->find('first', $options_pro);
                            $data['user_id'] = $sessionpatient['customer_info']['user']['id'];
                            $data['card_number'] = $sessionpatient['customer_info']['ClinicUser']['card_number'];
                            $data['first_name'] = $sessionpatient['customer_info']['user']['first_name'];
                            $data['last_name'] = $sessionpatient['customer_info']['user']['last_name'];
                            $data['activity_type'] = 'N';

                            if (!empty($Promotions)) {
                                $data['promotion_id'] = $Promotions['Promotion']['id'];
                                $data['amount'] = $Promotions['Promotion']['value'];
                            } else {
                                $data['amount'] = 100;
                            }
                            $data['activity_type'] = 'N';
                            $data['authorization'] = 'facebook point allocation';
                            $data['clinic_id'] = $sessionpatient['clinic_id'];
                            $data['date'] = date('Y-m-d H:i:s');
                            $data['status'] = 'New';
                            $data['is_buzzydoc'] = $sessionpatient['is_buzzydoc'];

                            $this->Transaction->create();

                            if ($this->Transaction->save($data)) {

                                $getfirstTransaction = $this->Api->get_firsttransaction($sessionpatient['customer_info']['user']['id'], $sessionpatient['clinic_id']);
                                if ($getfirstTransaction == 1 && $sessionpatient['customer_info']['user']['email'] != '' && $data['amount']>0) {
                                    $template_array = $this->Api->get_template(39);
                                    $link1 = str_replace('[username]', $sessionpatient['customer_info']['user']['first_name'], $template_array['content']);
                                    $link = str_replace('[points]', $data['amount'], $link1);
                                    $link2 = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $link);
                                    $Email = new CakeEmail(MAILTYPE);

                                    $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));

                                    $Email->to($sessionpatient['customer_info']['user']['email']);
                                    $Email->subject($template_array['subject'])
                                            ->template('buzzydocother')
                                            ->emailFormat('html');
                                    $Email->viewVars(array('msg' => $link2
                                    ));
                                    $Email->send();
                                }

                                $options2['conditions'] = array('Notification.user_id' => $sessionpatient['customer_info']['user']['id'], 'Notification.clinic_id' => $sessionpatient['clinic_id'], 'Notification.earn_points' => 1);
                                $Notifications = $this->Notification->find('first', $options2);
                                if (!empty($Notifications) && $sessionpatient['customer_info']['user']['email'] != '' && $data['amount']>0) {
                                    $rewardlogin = rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/login/" . base64_encode('redeem') . "/" . base64_encode($sessionpatient['customer_info']['ClinicUser']['card_number']) . "/" . base64_encode($sessionpatient['customer_info']['user']['id']) . "/Unsubscribe";


                                    $template_array = $this->Api->get_template(1);
                                    $link = str_replace('[username]', $sessionpatient['customer_info']['user']['first_name'], $template_array['content']);
                                    $link1 = str_replace('[click_here]', '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . '">Click Here</a>', $link);
                                    $link2 = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $link1);
                                    $link3 = str_replace('[points]', $data['amount'], $link2);
                                    $Email = new CakeEmail(MAILTYPE);

                                    $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));

                                    $Email->to($sessionpatient['customer_info']['user']['email']);
                                    $Email->subject($template_array['subject'])
                                            ->template('buzzydocother')
                                            ->emailFormat('html');
                                    $Email->viewVars(array('msg' => $link3
                                    ));
                                    $Email->send();
                                }
                                $this->ClinicUser->query("update clinic_users set facebook_like_status=1,facebook_email='" . $user_fb_email . "' where user_id=" . $sessionpatient['customer_info']['user']['id'] . " and card_number=" . $sessionpatient['customer_info']['ClinicUser']['card_number']);

                                if ($sessionpatient['is_buzzydoc'] == 1) {
                                    $options['conditions'] = array('User.id' => $sessionpatient['customer_info']['user']['id']);

                                    $userpoint = $this->User->find('first', $options);
                                    $totalpoint = $userpoint['User']['points'] + $data['amount'];
                                    $this->User->query("UPDATE `users` SET `points` = '" . $totalpoint . "' WHERE `id` =" . $sessionpatient['customer_info']['user']['id']);
                                    $this->Session->write('patient.customer_info.user.points', $totalpoint);
                                } else {
                                    $options['conditions'] = array('ClinicUser.user_id' => $sessionpatient['customer_info']['user']['id'], 'ClinicUser.clinic_id' => $sessionpatient['clinic_id']);

                                    $userpoint = $this->ClinicUser->find('first', $options);
                                    $totalpoint = $userpoint['ClinicUser']['local_points'] + $data['amount'];
                                    $this->User->query("UPDATE `clinic_users` SET `local_points` = '" . $totalpoint . "' WHERE `user_id` =" . $sessionpatient['customer_info']['user']['id'] . ' and clinic_id=' . $sessionpatient['clinic_id']);
                                    $this->Session->write('patient.customer_info.ClinicUser.local_points', $totalpoint);
                                }

                                $this->Session->write('patient.customer_info.ClinicUser.facebook_like_status', 1);
                                $this->set('errorMsg', "We've credited 100 points to you as     we found that you've already liked our Facebook page. Thanks!");
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            CakeLog::write('error', 'Data-Rohitortho-facebookuser' . print_r($user, true));
        }
        /*         * ***************facebook like check end here********************* */

        if ($this->request->is('post')) {
            $selectedmonth = $this->request->data['my_dropdown'];
        } else {
            $selectedmonth = date('n');
        }
        $options['conditions'] = array('OR' => array('Transaction.clinic_id' => $sessionpatient['clinic_id'], 'Transaction.redeem_from' => $sessionpatient['clinic_id']), 'Transaction.user_id' => $sessionpatient['customer_info']['user']['id']);
        $options['order'] = array('Transaction.date desc');
        //getting the all transaction details for patient.
        $Transaction = $this->Transaction->find('all', $options);
        $this->set('Transaction', $Transaction);
        $this->set('selectedmonth', $selectedmonth);
    }

    /**
     * Checking the user already exist with pratice or any other pratice if exist with any other practice then provide the option for link with othe pratice.
     */
    public function checkuserexist() {
        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');
        if (isset($_POST['parents_email'])) {
            $users_field_check = $this->User->find('first', array(
                'joins' => array(
                    array(
                        'table' => 'clinic_users',
                        'alias' => 'clinic_users',
                        'type' => 'INNER',
                        'conditions' => array(
                            'clinic_users.user_id = User.id'
                        )
                    )
                ),
                'conditions' => array(
                    'clinic_users.clinic_id' => $sessionpatient['clinic_id'],
                    'User.email' => $_POST['email'],
                    'User.parents_email' => $_POST['parents_email'],
                    'User.custom_date' => $_POST['dob'],
                    'User.blocked !=' => 1
                ),
                'fields' => array('User.id')
            ));
        } else {
            $date13age = date("Y-m-d", strtotime("-18 year"));
            $users_field_check = $this->User->find('first', array(
                'joins' => array(
                    array(
                        'table' => 'clinic_users',
                        'alias' => 'clinic_users',
                        'type' => 'INNER',
                        'conditions' => array(
                            'clinic_users.user_id = User.id'
                        )
                    )
                ),
                'conditions' => array(
                    'clinic_users.clinic_id' => $sessionpatient['clinic_id'],
                    'User.email' => $_POST['email'],
                    'User.custom_date <=' => $date13age,
                    'User.blocked !=' => 1
                ),
                'fields' => array('User.id')
            ));
        }
        if (empty($users_field_check)) {
            if (isset($_POST['parents_email'])) {
                $users_field = $this->User->find('all', array(
                    'joins' => array(
                        array(
                            'table' => 'clinic_users',
                            'alias' => 'clinic_users',
                            'type' => 'INNER',
                            'conditions' => array(
                                'clinic_users.user_id = User.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'clinic_users.clinic_id !=' => $sessionpatient['clinic_id'],
                        'User.email' => $_POST['email'],
                        'User.parents_email' => $_POST['parents_email'],
                        'User.custom_date' => $_POST['dob'],
                        'User.blocked !=' => 1
                    ),
                    'fields' => array('User.email', 'User.id', 'clinic_users.clinic_id')
                ));
            } else {
                $date13age = date("Y-m-d", strtotime("-18 year"));
                $users_field = $this->User->find('all', array(
                    'joins' => array(
                        array(
                            'table' => 'clinic_users',
                            'alias' => 'clinic_users',
                            'type' => 'INNER',
                            'conditions' => array(
                                'clinic_users.user_id = User.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'clinic_users.clinic_id !=' => $sessionpatient['clinic_id'],
                        'User.email' => $_POST['email'],
                        'User.custom_date <=' => $date13age,
                        'User.blocked !=' => 1
                    ),
                    'fields' => array('User.email', 'User.id', 'clinic_users.clinic_id')
                ));
            }
            if (count($users_field) > 0) {
                echo 1;
            } else {
                echo 0;
            }
        } else {
            echo 0;
        }
        exit;
    }

    /**
     * Linking card number with any other pratice card number and send success mail for linking.
     * @param type $emailid
     * @param type $card_number
     * @param type $pemail
     * @return type
     */
    public function linkwithcard($emailid, $card_number, $pemail = '') {
        $this->layout = "patientLayoutLogin";

        $sessionpatient = $this->Session->read('patient');
        $users_field = array();
        if (isset($emailid)) {
            if ($pemail != '') {
                $users_field = $this->User->find('all', array(
                    'joins' => array(
                        array(
                            'table' => 'clinic_users',
                            'alias' => 'clinic_users',
                            'type' => 'INNER',
                            'conditions' => array(
                                'clinic_users.user_id = User.id'
                            )
                        ),
                        array(
                            'table' => 'clinics',
                            'alias' => 'clinics',
                            'type' => 'INNER',
                            'conditions' => array(
                                'clinics.id = clinic_users.clinic_id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'clinic_users.clinic_id !=' => $sessionpatient['clinic_id'],
                        'User.email' => base64_decode($emailid),
                        'User.parents_email' => base64_decode($pemail),
                        'User.blocked !=' => 1
                    ),
                    'fields' => array('User.*', 'clinic_users.*', 'clinics.api_user')
                ));
            } else {

                $date13age = date("Y-m-d", strtotime("-18 year"));
                $users_field = $this->User->find('all', array(
                    'joins' => array(
                        array(
                            'table' => 'clinic_users',
                            'alias' => 'clinic_users',
                            'type' => 'INNER',
                            'conditions' => array(
                                'clinic_users.user_id = User.id'
                            )
                        ),
                        array(
                            'table' => 'clinics',
                            'alias' => 'clinics',
                            'type' => 'INNER',
                            'conditions' => array(
                                'clinics.id = clinic_users.clinic_id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'clinic_users.clinic_id !=' => $sessionpatient['clinic_id'],
                        'User.email' => base64_decode($emailid),
                        'User.custom_date <=' => $date13age,
                        'User.blocked !=' => 1
                    ),
                    'fields' => array('User.*', 'clinic_users.*', 'clinics.api_user')
                ));
            }
        }

        if (count($users_field) > 0) {
            $this->set('email_link', $users_field);
            $this->set('card_number', base64_decode($card_number));
        } else {
            $this->Session->setFlash(__('You have already linked.'));
            return $this->redirect('/rewards/login/');
        }

        if (isset($this->request->data['link_to_email'])) {
            $users_pass = $this->User->find('first', array(
                'conditions' => array(
                    'User.id' => $this->request->data['user_id'],
                ),
                'fields' => array('User.*')
            ));
            $ClinicUser_vl = array("ClinicUser" => array("clinic_id" => $sessionpatient['clinic_id'], "user_id" => $this->request->data['user_id'], "card_number" => $this->request->data['card_number']));
            $this->ClinicUser->create();
            $this->ClinicUser->save($ClinicUser_vl);
            $this->CardNumber->query("UPDATE `card_numbers` SET `status` = 2  WHERE `clinic_id` =" . $sessionpatient['clinic_id'] . " and card_number='" . $this->request->data['card_number'] . "'");
            //getting the transaction details for taged card number who goes to link with account.
            $alltrans = $this->UnregTransaction->find('all', array(
                'conditions' => array(
                    'UnregTransaction.user_id' => 0,
                    'UnregTransaction.card_number' => $this->request->data['card_number'],
                    'UnregTransaction.clinic_id' => $sessionpatient['clinic_id']
                )
            ));

            foreach ($alltrans as $newtran) {
                $datatrans['user_id'] = $this->request->data['user_id'];
                $datatrans['staff_id'] = $newtran['UnregTransaction']['staff_id'];
                $datatrans['card_number'] = $this->request->data['card_number'];
                $datatrans['first_name'] = $users_pass['User']['first_name'];
                $datatrans['last_name'] = $users_pass['User']['last_name'];
                $datatrans['promotion_id'] = $newtran['UnregTransaction']['promotion_id'];
                $datatrans['amount'] = $newtran['UnregTransaction']['amount'];
                $datatrans['activity_type'] = $newtran['UnregTransaction']['activity_type'];
                $datatrans['authorization'] = $newtran['UnregTransaction']['authorization'];
                $datatrans['clinic_id'] = $newtran['UnregTransaction']['clinic_id'];
                $datatrans['date'] = $newtran['UnregTransaction']['date'];
                $datatrans['status'] = $newtran['UnregTransaction']['status'];
                $datatrans['is_buzzydoc'] = $sessionpatient['is_buzzydoc'];
                $this->Transaction->create();
                $this->Transaction->save($datatrans);
                $this->UnregTransaction->deleteAll(array(
                    'UnregTransaction.id' => $newtran['UnregTransaction']['id'],
                    false
                ));
            }

            $allpoints = $this->Transaction->find('first', array(
                'conditions' => array(
                    'Transaction.user_id' => $this->request->data['user_id'],
                    'Transaction.is_buzzydoc' => 1
                ),
                'fields' => array(
                    'SUM(Transaction.amount) AS points'
                ),
                'group' => array(
                    'Transaction.card_number'
                )
            ));

            if ($allpoints[0]['points'] > 0) {
                $newpoints = $allpoints[0]['points'];
            } else {
                $newpoints = 0;
            }
            //update points to account after linking.
            $queryuser = 'update users set points=' . $newpoints . ' where id=' . $this->request->data['user_id'];
            $usersave = $this->User->query($queryuser);

            $allpoints1 = $this->Transaction->find('first', array(
                'conditions' => array(
                    'Transaction.user_id' => $this->request->data['user_id'],
                    'Transaction.clinic_id' => $sessionpatient['clinic_id'],
                    'Transaction.is_buzzydoc' => 0
                ),
                'fields' => array(
                    'SUM(Transaction.amount) AS points'
                ),
                'group' => array(
                    'Transaction.card_number'
                )
            ));

            if ($allpoints1[0]['points'] > 0) {
                $newpoints1 = $allpoints1[0]['points'];
            } else {
                $newpoints1 = 0;
            }

            $queryuser1 = 'update clinic_users set local_points=' . $newpoints1 . ' where user_id=' . $this->request->data['user_id'] . ' and clinic_id=' . $sessionpatient['clinic_id'];
            $usersave1 = $this->ClinicUser->query($queryuser1);

            $this->Session->setFlash(__('Your card has been linked to your BuzzyDoc Id (Your email on record). Please login with your existing BuzzyDoc credentials which we\'ve sent on your email.'));

            $template_array = $this->Api->get_template(34);
            $link = str_replace('[click_here]', '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . '">Click Here</a>', $template_array['content']);
            $link1 = str_replace('[username]', $users_pass['User']['first_name'], $link);
            $link2 = str_replace('[card_number]', base64_decode($card_number), $link1);
            $link3 = str_replace('[password]', $users_pass['User']['password'], $link2);
            $Email = new CakeEmail(MAILTYPE);

            $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));

            $Email->to($this->request->data['email']);
            $Email->subject($template_array['subject'])
                    ->template('buzzydocother')
                    ->emailFormat('html');
            $Email->viewVars(array('msg' => $link3
            ));
            $Email->send();
            return $this->redirect('/rewards/login/');
        }
    }

    /**
     * New user signup with card number.
     * @return type
     */
    public function signup() {
        $this->layout = "patientLayoutsignup";
        $sessionpatient = $this->Session->read('patient');
        //condition to check patient provided the card number or not.
        if (isset($this->request->data['login']['card_number'])) {
            $this->set('card_number', $this->request->data['login']['card_number']);
            //Condition for signup for new account.
        } else if ($this->request->is('post') && isset($this->request->data['action']) && $this->request->data['action'] == 'record_new_account') {

            foreach ($this->request->data as $allfield1 => $allfieldval1) {
                $checkfield = explode('_', $allfield1);
                if ($checkfield[0] == 'other') {

                    $findfield = str_replace('other_', '', $allfield1);
                    $newarray[$findfield] = $allfieldval1;
                    unset($this->request->data[$allfield1]);
                }
            }

            foreach ($this->request->data as $allfield => $allfieldval) {
                if (is_array($allfieldval)) {
                    $this->request->data[$allfield] = implode(',', $allfieldval);
                } else {
                    $this->request->data[$allfield] = $allfieldval;
                }

                if (isset($newarray[$allfield])) {

                    $this->request->data[$allfield] = $this->request->data[$allfield] . '###' . $newarray[$allfield];
                }
            }

            if (!isset($this->request->data['custom_date'])) {
                $this->request->data['custom_date'] = $this->request->data['date_year'] . '-' . $this->request->data['date_month'] . '-' . $this->request->data['date_day'];
            }

            if (isset($this->request->data['parents_email'])) {
                $this->request->data['email'] = $this->request->data['parents_email'];

                if ($this->request->data['emailprovide'] == 'own') {
                    $is_parents = 0;
                    $this->request->data['is_verified'] = 1;
                } else {
                    $is_parents = 1;
                    $this->request->data['is_verified'] = 0;
                }
                $this->request->data['parents_email'] = $this->request->data['aemail'];
            } else {
                $this->request->data['parents_email'] = '';
                $this->request->data['is_verified'] = 1;
                $is_parents = 0;
            }

            $Patient = $this->ClinicUser->find('first', array(
                'conditions' => array(
                    'ClinicUser.card_number' => $this->request->data['card'],
                    'ClinicUser.clinic_id' => $sessionpatient['clinic_id']
                )
            ));
            if (empty($Patient)) {

                $Patients_ch = $this->User->find('all', array(
                    'conditions' => array('OR' => array('User.email' => $this->request->data['email'], 'User.parents_email' => $this->request->data['email'])
                    ),
                    'fields' => array('User.*')
                ));

                if (!empty($Patients_ch)) {
                    $ag_checked = 0;
                    foreach ($Patients_ch as $pt) {

                        $date1_chd1 = $pt['User']['custom_date'];
                        $date1_chd1 = date('Y-m-d', strtotime('+4 days', strtotime($date1_chd1)));
                        $date2_chd1 = date('Y-m-d');
                        $diff_chd = abs(strtotime($date2_chd1) - strtotime($date1_chd1));
                        $years_chd1 = floor($diff_chd / (365 * 60 * 60 * 24));
                        if ($years_chd1 > 18) {
                            $ag_checked = 1;
                        }
                    }
                }
                $Patients_ch_child = $this->User->find('all', array(
                    'conditions' => array('User.email !=' => $this->request->data['email'], 'User.parents_email' => $this->request->data['parents_email'], 'User.parents_email !=' => ''
                    ),
                    'fields' => array('User.*')
                ));
                $Patients_pr_child = $this->User->find('all', array(
                    'conditions' => array('User.email' => $this->request->data['email'], 'User.parents_email' => $this->request->data['parents_email'], 'User.parents_email !=' => ''
                    ),
                    'fields' => array('User.*')
                ));
                //checking the signup for child account or adult.
                if (isset($ag_checked) && !empty($Patients_ch) && $ag_checked == 1) {

                    $date1_chd = $this->request->data['custom_date'];

                    $date1_chd = date('Y-m-d', strtotime('+4 days', strtotime($date1_chd)));
                    $date2_chd = date('Y-m-d');
                    $diff_chd = abs(strtotime($date2_chd) - strtotime($date1_chd));
                    $years_chd = floor($diff_chd / (365 * 60 * 60 * 24));
                    if ($years_chd < 18) {
                        if ($this->request->data['parents_email'] != '') {
                            $Patients_ch1 = $this->User->find('first', array(
                                'conditions' => array('User.parents_email' => $this->request->data['parents_email']
                                ),
                                'fields' => array('User.*')
                            ));
                        } else {
                            $Patients_ch1 = array();
                        }

                        $Patients_ch3 = $this->User->find('first', array(
                            'conditions' => array('User.parents_email' => $this->request->data['email']
                            )
                        ));
                        if (!empty($Patients_ch1)) {
                            $child_exist = 1;
                        }
                        if (!empty($Patients_ch3)) {
                            $parent_exist = 1;
                        }
                    }
                }

                if (!empty($Patients_ch) && $ag_checked == 0) {
                    $Patients_ch1 = $this->User->find('first', array(
                        'conditions' => array('User.parents_email' => $this->request->data['email']
                        )
                    ));

                    if (!empty($Patients_ch1)) {
                        $parent_exist = 1;
                    }
                }


                if (isset($years_chd) && $years_chd > 18) {
                    $this->Session->setFlash(__('Email already exists. Use different email id.'));
                    return $this->redirect('/rewards/login/');
                } else if (isset($Patients_pr_child) && !empty($Patients_pr_child)) {
                    $this->Session->setFlash(__('Username already exists.'));
                    return $this->redirect('/rewards/login/');
                } else if (isset($Patients_ch_child) && !empty($Patients_ch_child)) {
                    $this->Session->setFlash(__('Username already exists.'));
                    return $this->redirect('/rewards/login/');
                } else if (isset($parent_exist) && $parent_exist == 1) {
                    $this->Session->setFlash(__('Email already exists. Use different email id.'));
                    return $this->redirect('/rewards/login/');
                } else if (isset($child_exist) && $child_exist == 1) {
                    $this->Session->setFlash(__('Username already exists.'));
                    return $this->redirect('/rewards/login/');
                } else {
                    if (isset($this->request->data['is_facebook'])) {
                        $is_fb = 1;
                        $fb_id = $this->request->data['facebook_id'];
                        $new_password = dechex(time()) . mt_rand(0, 100000);
                        $this->request->data['new_password'] = $new_password;
                    } else {
                        $is_fb = 0;
                        $fb_id = 0;
                        $this->request->data['new_password'] = $this->request->data['new_password'];
                    }
                    $Patients_array['User'] = array(
                        'custom_date' => $this->request->data['custom_date'],
                        'email' => strtolower($this->request->data['email']),
                        'parents_email' => strtolower($this->request->data['parents_email']),
                        'first_name' => $this->request->data['first_name'],
                        'last_name' => $this->request->data['last_name'],
                        'customer_password' => md5($this->request->data['new_password']),
                        'password' => $this->request->data['new_password'],
                        'points' => 0,
                        'enrollment_stamp' => date('Y-m-d H:i:s'),
                        'facebook_id' => $fb_id,
                        'is_facebook' => $is_fb,
                        'status' => 1,
                        'is_verified' => $this->request->data['is_verified']
                    );

                    $this->User->create();
                    $this->User->save($Patients_array);
                    $user_id = $this->User->getLastInsertId();

                    $Patients_CU_array['ClinicUser'] = array('clinic_id' => $sessionpatient['clinic_id'],
                        'user_id' => $user_id,
                        'card_number' => $this->request->data['card'],
                        'facebook_like_status' => 0
                    );
                    $this->ClinicUser->create();
                    $this->ClinicUser->save($Patients_CU_array);

                    $this->CardNumber->query("UPDATE `card_numbers` SET `status` = 2  WHERE `clinic_id` =" . $sessionpatient['clinic_id'] . " and card_number='" . $this->request->data['card'] . "'");
                    $alltrans = $this->UnregTransaction->find('all', array(
                        'conditions' => array(
                            'UnregTransaction.user_id' => 0,
                            'UnregTransaction.card_number' => $this->request->data['card'],
                            'UnregTransaction.clinic_id' => $sessionpatient['clinic_id']
                        )
                    ));
                    $firstamount = 0;
                    foreach ($alltrans as $newtran) {
                        $datatrans['user_id'] = $user_id;
                        $datatrans['staff_id'] = $newtran['UnregTransaction']['staff_id'];
                        $datatrans['card_number'] = $this->request->data['card'];
                        $datatrans['first_name'] = $this->request->data['first_name'];
                        $datatrans['last_name'] = $this->request->data['last_name'];
                        $datatrans['promotion_id'] = $newtran['UnregTransaction']['promotion_id'];
                        $datatrans['amount'] = $newtran['UnregTransaction']['amount'];
                        $datatrans['activity_type'] = $newtran['UnregTransaction']['activity_type'];
                        $datatrans['authorization'] = $newtran['UnregTransaction']['authorization'];
                        $datatrans['clinic_id'] = $newtran['UnregTransaction']['clinic_id'];
                        $datatrans['date'] = $newtran['UnregTransaction']['date'];
                        $datatrans['status'] = $newtran['UnregTransaction']['status'];
                        $datatrans['is_buzzydoc'] = $sessionpatient['is_buzzydoc'];
                        $this->Transaction->create();
                        $this->Transaction->save($datatrans);
                        $this->UnregTransaction->deleteAll(array(
                            'UnregTransaction.id' => $newtran['UnregTransaction']['id'],
                            false
                        ));
                        if ($firstamount < 1) {
                            $firstamount = $newtran['UnregTransaction']['amount'];
                        }
                    }
                    $staff_access = $this->AccessStaff->getAccessForClinic($sessionpatient['clinic_id']);
                    //If self registration is On for pratice then bonus point auto assign to account.
                    if ($staff_access['AccessStaff']['self_registration'] == 1) {
                        $optionsdef['conditions'] = array('Promotion.clinic_id' => $sessionpatient['clinic_id'], 'Promotion.description' => 'Self Registration Bonus', 'Promotion.public' => 1);
                        $getdefaultpro = $this->Promotion->find('first', $optionsdef);
                        $datatransself['user_id'] = $user_id;
                        $datatransself['staff_id'] = 0;
                        $datatransself['card_number'] = $this->request->data['card'];
                        $datatransself['first_name'] = $this->request->data['first_name'];
                        $datatransself['last_name'] = $this->request->data['last_name'];
                        $datatransself['promotion_id'] = $getdefaultpro['Promotion']['id'];
                        $datatransself['amount'] = $getdefaultpro['Promotion']['value'];
                        $datatransself['activity_type'] = 'N';
                        $datatransself['authorization'] = $getdefaultpro['Promotion']['description'];
                        $datatransself['clinic_id'] = $sessionpatient['clinic_id'];
                        $datatransself['date'] = date('Y-m-d H:i:s');
                        $datatransself['status'] = 'New';
                        $datatransself['is_buzzydoc'] = $sessionpatient['is_buzzydoc'];
                        $this->Transaction->create();
                        $this->Transaction->save($datatransself);
                        if ($firstamount < 1) {
                            $firstamount = $getdefaultpro['Promotion']['value'];
                        }
                    }
                    //Function for if first time get the points.
                    $getfirstTransaction = $this->Api->get_firsttransaction($user_id, $sessionpatient['clinic_id']);
                    if ($getfirstTransaction == 1 && $this->request->data['email'] != '' && $firstamount>0) {

                        $template_array = $this->Api->get_template(39);
                        $link1 = str_replace('[username]', $this->request->data['first_name'], $template_array['content']);
                        $link = str_replace('[points]', $firstamount, $link1);
                        $link2 = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $link);
                        $Emailnew = new CakeEmail(MAILTYPE);
                        $Emailnew->from(array(
                            SUPER_ADMIN_EMAIL => 'BuzzyDoc'
                        ));
                        $Emailnew->to($this->request->data['email']);
                        $Emailnew->subject($template_array['subject'])
                                ->template('buzzydocother')
                                ->emailFormat('html');
                        $Emailnew->viewVars(array(
                            'msg' => $link2
                        ));
                        $Emailnew->send();
                    }
                    $allpoints = $this->Transaction->find('first', array(
                        'conditions' => array(
                            'Transaction.user_id' => $user_id,
                            'Transaction.clinic_id' => $sessionpatient['clinic_id'],
                            'Transaction.is_buzzydoc' => $sessionpatient['is_buzzydoc']
                        ),
                        'fields' => array(
                            'SUM(Transaction.amount) AS points'
                        ),
                        'group' => array(
                            'Transaction.card_number'
                        )
                    ));

                    if ($allpoints[0]['points'] > 0) {
                        $newpoints = $allpoints[0]['points'];
                    } else {
                        $newpoints = 0;
                    }
                    if ($sessionpatient['is_buzzydoc'] == 1) {
                        $queryuser = 'update users set points=' . $newpoints . ' where id=' . $user_id;
                        $usersave = $this->User->query($queryuser);
                    } else {
                        $queryuser = 'update clinic_users set local_points=' . $newpoints . ' where user_id=' . $user_id . ' and clinic_id=' . $sessionpatient['clinic_id'];
                        $usersave = $this->ClinicUser->query($queryuser);
                    }

                    foreach ($sessionpatient['ProFieldGlobal'] as $val) {

                        if (isset($this->request->data[$val['ProfileField']['profile_field']])) {
                            $pr_val = $this->request->data[$val['ProfileField']['profile_field']];
                        } else {
                            $pr_val = '';
                        }
                        $records_pf_vl = array(
                            "ProfileFieldUser" => array(
                                "user_id" => $user_id,
                                "profilefield_id" => $val['ProfileField']['id'],
                                "value" => $pr_val,
                                "clinic_id" => 0
                            )
                        );
                        $this->ProfileFieldUser->create();
                        $this->ProfileFieldUser->save($records_pf_vl);
                    }

                    if ($is_parents == 0) { //user
                        $template_array = $this->Api->get_template(4);
                        $link = str_replace('[first_name]', $this->request->data['first_name'], $template_array['content']);
                        $link1 = str_replace('[username]', $this->request->data['card'], $link);
                        $link2 = str_replace('[password]', $this->request->data['new_password'], $link1);
                        $link3 = str_replace('[click_here]', '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . '">Click Here</a>', $link2);
                        $sub = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $template_array['subject']);
                        $Email = new CakeEmail(MAILTYPE);
                        $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
                        $Email->to($this->request->data['email']);
                        $Email->subject($sub)
                                ->template('buzzydocother')
                                ->emailFormat('html');
                        $Email->viewVars(array('msg' => $link3,
                            'theme' => $sessionpatient['Themes']
                        ));
                        $Email->send();
                    } else { //parent
                        $template_array = $this->Api->get_template(36);
                        $link = str_replace('[click_here]', "<a href='" . rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/login/" . base64_encode($sessionpatient['clinic_id']) . "/" . base64_encode($user_id) . "' >Click Here</a>", $template_array['content']);
                        $link1 = str_replace('[card_number]', $this->request->data['card'], $link);
                        $link2 = str_replace('[first_name]', $this->request->data['first_name'], $link1);
                        $link3 = str_replace('[last_name]', $this->request->data['last_name'], $link2);
                        $sub = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $template_array['subject']);
                        $Email = new CakeEmail(MAILTYPE);
                        $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
                        $Email->to($this->request->data['email']);
                        $Email->subject($sub)
                                ->template('buzzydocother')
                                ->emailFormat('html');
                        $Email->viewVars(array('msg' => $link3
                        ));
                        $Email->send();
                    }
                    //if account is child account then email goes to adult account for verification.
                    if (isset($this->request->data['parents_email']) && $this->request->data['parents_email'] != '') {
                        $this->Session->setFlash(__("Form submitted successfully. An email is sent to the parent's account for approval."));
                        return $this->redirect('/rewards/login/');
                    } else {

                        if (isset($this->request->data['is_facebook'])) {
                            $Patients_get = $this->ClinicUser->find('first', array(
                                'joins' => array(
                                    array(
                                        'table' => 'users',
                                        'alias' => 'user',
                                        'type' => 'INNER',
                                        'conditions' => array(
                                            'user.id = ClinicUser.user_id'
                                        )
                                    )),
                                'conditions' => array(
                                    'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                                    'ClinicUser.user_id' => $user_id,
                                    'user.email' => $this->request->data['email'],
                                    'user.is_facebook' => 1,
                                    'user.is_verified' => 1
                                ),
                                'fields' => array('ClinicUser.*', 'user.*')
                            ));
                            if (isset($Patients_get) && !empty($Patients_get)) {
                                if (isset($sessionpatient['api_user'])) {
                                    $this->loadModel('user');
                                    $Patients = $this->user->find('first', array('conditions' => array('user.id' => $Patients_get['ClinicUser']['user_id'])));

                                    if ($Patients['user']['is_verified'] == 1) {

                                        foreach ($Patients['Clinic'] as $clinic) {
                                            if ($clinic['id'] == $sessionpatient['clinic_id']) {
                                                $userglobalval = $clinic['ClinicUser'];
                                            }
                                        }
                                        $pfieldarray = array();
                                        foreach ($Patients['ProfileField'] as $ProfileField) {
                                            if ($ProfileField['profile_field'] == 'gender') {
                                                $ProfileFieldval = $ProfileField['ProfileFieldUser']['value'];
                                            }
                                            if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                                                $pfieldarray[] = $ProfileField;
                                            }
                                        }

                                        $Patients['ProfileField'] = $pfieldarray;
                                        $Reward = $this->Reward->find('all', array('conditions' => array('Reward.clinic_id' => $sessionpatient['clinic_id'], 'Reward.points !=' => '', 'Reward.description !=' => '')));
                                        if ($sessionpatient['is_buzzydoc'] == 1) {
                                            return $this->redirect(Buzzy_Name . 'buzzydoc/login/' . base64_encode($Patients['user']['id']));
                                        } else {
                                            if ($Patients['user']['first_name'] == '' || $Patients['user']['last_name'] == '' || $Patients['user']['email'] == '' || $ProfileFieldval == '' || $Patients['user']['custom_date'] == '') {

                                                $this->Session->write('patient.Reward', $Reward);
                                                $this->Session->write('patient.var.patient_name', $Patients_get['ClinicUser']['card_number']);
                                                $this->Session->write('patient.var.patient_password', $Patients_get['user']['password']);
                                                $this->Session->write('patient.customer_info', $Patients);
                                                $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                                                $this->Session->setFlash(__('Please fill in the mandatory fields before proceeding'));
                                                return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                                            } else {
                                                $this->Session->write('patient.Reward', $Reward);
                                                $this->Session->write('patient.var.patient_name', $Patients_get['ClinicUser']['card_number']);
                                                $this->Session->write('patient.var.patient_password', $Patients_get['user']['password']);
                                                $this->Session->write('patient.customer_info', $Patients);
                                                $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                                                return $this->redirect(array('controller' => 'rewards', 'action' => 'home'));
                                            }
                                        }
                                    } else {
                                        $this->Session->setFlash(__("Waiting on parent's email confirmation."));
                                    }
                                } else {
                                    $this->Session->setFlash(__('Clinic does not exists.'));
                                }
                            }
                        } else {
                            $this->Session->setFlash(__('Sign Up completed use your credential for login.'));
                            return $this->redirect('/rewards/login/');
                        }
                    }
                }
            } else {
                $this->Session->setFlash(__('Patient Already exists.'));
                return $this->redirect('/rewards/login/');
            }
            //Condition for email exist with other pratice account the option to link with that card number.
        } else if ($this->request->is('post') && isset($this->request->data['action']) && $this->request->data['action'] == 'record_exist_account') {

            $this->User->query("UPDATE `users` SET `email` = '" . strtolower($this->request->data['emailexist']) . "',`last_name` = '" . $this->request->data['last_name_exist'] . "',`first_name` = '" . $this->request->data['first_name_exist'] . "',`customer_password`=md5('" . $this->request->data['new_password_exist'] . "'),`password`='" . $this->request->data['new_password_exist'] . "' WHERE `id` =" . $this->request->data['id']);
            $this->loadModel('user');
            $Patients = $this->user->find('first', array('conditions' => array('user.id' => $this->request->data['id'])));
            $date13age = date("Y-m-d", strtotime("-18 year"));

            if ($Patients['user']['custom_date'] != '' && $Patients['user']['custom_date'] != '0000-00-00' && $Patients['user']['custom_date'] > $date13age) {
                $this->User->query("UPDATE `users` SET `is_verified`=0 WHERE `id` =" . $this->request->data['id']);
                $template_array = $this->Api->get_template(36);

                $link = str_replace('[click_here]', "<a href='" . rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/login/" . base64_encode($sessionpatient['clinic_id']) . "/" . base64_encode($this->request->data['id']) . "' >Click Here</a>", $template_array['content']);
                $link1 = str_replace('[card_number]', $this->request->data['cardexist'], $link);
                $link2 = str_replace('[first_name]', $this->request->data['first_name_exist'], $link1);
                $link3 = str_replace('[last_name]', $this->request->data['last_name_exist'], $link2);
                $sub = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $template_array['subject']);
                $Email = new CakeEmail(MAILTYPE);
                $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
                $Email->to($this->request->data['emailexist']);
                $Email->subject($subj)
                        ->template('buzzydocother')
                        ->emailFormat('html');
                $Email->viewVars(array('msg' => $link3
                ));
                $Email->send();
                $this->Session->setFlash(__("Waiting on parent's email confirmation."));
                return $this->redirect(array('controller' => 'rewards', 'action' => 'login'));
            } else {
                foreach ($Patients['Clinic'] as $clinic) {
                    if ($clinic['id'] == $sessionpatient['clinic_id']) {
                        $userglobalval = $clinic['ClinicUser'];
                    }
                }
                $pfieldarray = array();
                foreach ($Patients['ProfileField'] as $ProfileField) {
                    if ($ProfileField['profile_field'] == 'gender') {
                        $ProfileFieldval = $ProfileField['ProfileFieldUser']['value'];
                    }
                    if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                        $pfieldarray[] = $ProfileField;
                    }
                }

                $Patients['ProfileField'] = $pfieldarray;
                $Reward = $this->Reward->find('all', array('conditions' => array('Reward.clinic_id' => $sessionpatient['clinic_id'], 'Reward.points !=' => '', 'Reward.description !=' => '')));
                if ($sessionpatient['is_buzzydoc'] == 1) {
                    return $this->redirect(Buzzy_Name . 'buzzydoc/login/' . base64_encode($Patients['user']['id']));
                } else {
                    if ($Patients['user']['first_name'] == '' || $Patients['user']['last_name'] == '' || $Patients['user']['email'] == '' || $ProfileFieldval == '' || $Patients['user']['custom_date'] == '') {

                        $this->Session->write('patient.Reward', $Reward);
                        $this->Session->write('patient.var.patient_name', $userglobalval['card_number']);
                        $this->Session->write('patient.var.patient_password', $Patients['user']['password']);
                        $this->Session->write('patient.customer_info', $Patients);
                        $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                        $this->Session->setFlash(__('Please fill in the mandatory fields before proceeding'));
                        return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                    } else {
                        $this->Session->write('patient.Reward', $Reward);
                        $this->Session->write('patient.var.patient_name', $userglobalval['card_number']);
                        $this->Session->write('patient.var.patient_password', $Patients['user']['password']);
                        $this->Session->write('patient.customer_info', $Patients);
                        $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                        return $this->redirect(array('controller' => 'rewards', 'action' => 'home'));
                    }
                }
            }
        } else if ($this->request->is('post') && isset($this->request->data['action']) && $this->request->data['action'] == 'link') {

            if (isset($this->request->data['parents_email'])) {
                $email = $this->request->data['parents_email'];
                $pemail = $this->request->data['aemail'];
            } else {
                $email = $this->request->data['email'];
            }
            $linkstr = "";
            if (isset($pemail)) {

                $link_str = '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/linkwithcard/" . base64_encode($email) . "/" . base64_encode($this->request->data['card']) . "/" . base64_encode($pemail) . '">Link Url</a>';
            } else {
                $link_str = '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/linkwithcard/" . base64_encode($email) . "/" . base64_encode($this->request->data['card']) . '">Link Url</a>';
            }
            $link = str_replace('[link_url]', $link, $template_array['content']);
            $link1 = str_replace('[username]', $this->request->data['first_name'], $link);
            $template_array = $this->Api->get_template(25);
            $subject = str_replace('[card_number]', $this->request->data['card'], $template_array['subject']);
            $Email = new CakeEmail(MAILTYPE);
            $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
            $Email->to($email);
            $Email->subject($subject)
                    ->template('buzzydocother')
                    ->emailFormat('html');
            $Email->viewVars(array('msg' => $link1
            ));
            $Email->send();
            $this->Session->setFlash(__("Check your email for linking."));
            return $this->redirect('/rewards/login/');
        } else {
            return $this->redirect('/rewards/login/');
        }
        $state = $this->State->find('all');
        $this->set('states', $state);
    }

    /**
     * Login and signup with facebook.
     * @return type
     */
    public function facebooklogin() {
        if (isset($this->request->data['card1'])) {
            $this->Session->write('patient.var.facebook_card', $this->request->data['card1']);
        }
        $sessionpatient = $this->Session->read('patient');

        $facebook = new Facebook(array(
            'appId' => $sessionpatient['Themes']['fb_app_id'],
            'secret' => $sessionpatient['Themes']['fb_app_key'],
        ));
        $user = $facebook->getUser();
        if ($user) {
            try {
                $user_profile = $facebook->api('/me');
            } catch (FacebookApiException $e) {
                error_log($e);
                $user = null;
            }
        }
        if ($user) {
            $logoutUrl = $facebook->getLogoutUrl();
        } else {
            $loginUrl = $facebook->getLoginUrl(array('scope' => 'email,user_birthday'));
            return $this->redirect($loginUrl);
        }
        if (isset($user_profile['username'])) {
            
        } else {
            $user_profile['username'] = '';
        }

        if (isset($user_profile['birthday']) && !empty($user_profile['birthday'])) {
            $custdt = date('Y-m-d', strtotime($user_profile['birthday']));
        } else {
            $custdt = '0000-00-00';
        }
        $date13age = date("Y-m-d", strtotime("-18 year"));
        $Patients1 = $this->ClinicUser->find('first', array(
            'joins' => array(
                array(
                    'table' => 'users',
                    'alias' => 'user',
                    'type' => 'INNER',
                    'conditions' => array(
                        'user.id = ClinicUser.user_id'
                    )
                )),
            'conditions' => array(
                'user.email' => $user_profile['email'],
                'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                'user.custom_date <=' => $date13age,
                'user.blocked !=' => 1
            ),
            'fields' => array('ClinicUser.*', 'user.*')
        ));
        //if account already exist then login to account.
        if (!empty($Patients1)) {

            if (isset($sessionpatient['api_user'])) {

                $this->loadModel('user');
                $Patients = $this->user->find('first', array('conditions' => array('user.id' => $Patients1['user']['id'])));
                if ($Patients['user']['is_verified'] == 1) {

                    foreach ($Patients['Clinic'] as $clinic) {
                        if ($clinic['id'] == $sessionpatient['clinic_id']) {
                            $userglobalval = $clinic['ClinicUser'];
                        }
                    }
                    $pfieldarray = array();
                    foreach ($Patients['ProfileField'] as $ProfileField) {
                        if ($ProfileField['profile_field'] == 'gender') {
                            $ProfileFieldval = $ProfileField['ProfileFieldUser']['value'];
                        }
                        if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                            $pfieldarray[] = $ProfileField;
                        }
                    }
                    $Patients['ProfileField'] = $pfieldarray;
                    $Reward = $this->Reward->find('all', array('conditions' => array('Reward.clinic_id' => $sessionpatient['clinic_id'], 'Reward.points !=' => '', 'Reward.description !=' => '')));
                    $this->Session->write('patient.Reward', $Reward);
                    $this->Session->write('patient.var.patient_name', $userglobalval['card_number']);
                    $this->Session->write('patient.var.patient_password', $Patients['user']['password']);
                    $this->Session->write('patient.customer_info', $Patients);
                    $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                    if ($sessionpatient['is_buzzydoc'] == 1) {
                        return $this->redirect(Buzzy_Name . 'buzzydoc/login/' . base64_encode($Patients['user']['id']));
                    } else {
                        if ($Patients['user']['first_name'] == '' || $Patients['user']['last_name'] == '' || $Patients['user']['email'] == '' || $ProfileFieldval == '' || $Patients['user']['custom_date'] == '') {
                            $this->Session->setFlash(__('Please fill in the mandatory fields before proceeding'));
                            return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                        } else {
                            return $this->redirect(array('controller' => 'rewards', 'action' => 'home'));
                        }
                    }
                } else {
                    $this->Session->setFlash(__("Waiting on parent's email confirmation."));
                }
            }
            $this->Session->delete('patient.var');
        } else {
            //signup from facebook.
            $sessionpatient = $this->Session->read('patient');
            if (isset($sessionpatient['var']['facebook_card'])) {

                $data = array('card' => $sessionpatient['var']['facebook_card'], 'first_name' => $user_profile['first_name'], 'last_name' => $user_profile['last_name']
                    , 'email' => $user_profile['email'], 'customer_username' => $user_profile['username'], 'custom_date' => $custdt, 'gender' => $user_profile['gender'], 'facebook_id' => $user_profile['id'], 'is_facebook' => 1);
                $Patient = $this->ClinicUser->find('first', array(
                    'conditions' => array(
                        'ClinicUser.card_number' => $data['card'],
                        'ClinicUser.clinic_id' => $sessionpatient['clinic_id']
                    )
                ));
                if (empty($Patient)) {

                    $Patients_ch = $this->ClinicUser->find('all', array(
                        'joins' => array(
                            array(
                                'table' => 'users',
                                'alias' => 'user',
                                'type' => 'INNER',
                                'conditions' => array(
                                    'user.id = ClinicUser.user_id'
                                )
                            )),
                        'conditions' => array('OR' => array('user.email' => $data['email'], 'user.parents_email' => $data['email']),
                            'ClinicUser.card_number !=' => $data['card'],
                            'ClinicUser.clinic_id' => $sessionpatient['clinic_id']
                        ),
                        'fields' => array('user.*', 'ClinicUser.*')
                    ));


                    if (!empty($Patients_ch)) {
                        $ag_checked = 0;
                        foreach ($Patients_ch as $pt) {

                            $date1_chd1 = $pt['user']['custom_date'];
                            $date1_chd1 = date('Y-m-d', strtotime('+4 days', strtotime($date1_chd1)));
                            $date2_chd1 = date('Y-m-d');
                            $diff_chd = abs(strtotime($date2_chd1) - strtotime($date1_chd1));
                            $years_chd1 = floor($diff_chd / (365 * 60 * 60 * 24));
                            if ($years_chd1 > 18) {
                                $ag_checked = 1;
                            }
                        }
                    }

                    if (isset($ag_checked) && !empty($Patients_ch) && $ag_checked == 1) {

                        $date1_chd = $data['custom_date'];

                        $date1_chd = date('Y-m-d', strtotime('+4 days', strtotime($date1_chd)));
                        $date2_chd = date('Y-m-d');
                        $diff_chd = abs(strtotime($date2_chd) - strtotime($date1_chd));
                        $years_chd = floor($diff_chd / (365 * 60 * 60 * 24));
                        if ($years_chd < 18) {

                            $Patients_ch1 = array();

                            $Patients_ch1 = $this->ClinicUser->find('first', array(
                                'joins' => array(
                                    array(
                                        'table' => 'users',
                                        'alias' => 'user',
                                        'type' => 'INNER',
                                        'conditions' => array(
                                            'user.id = ClinicUser.user_id'
                                        )
                                    )),
                                'conditions' => array('user.parents_email' => $data['email'],
                                    'ClinicUser.card_number !=' => $data['card'],
                                    'ClinicUser.clinic_id' => $sessionpatient['clinic_id']
                                )
                            ));
                            if (!empty($Patients_ch1)) {
                                $child_exist = 1;
                            }
                            if (!empty($Patients_ch3)) {
                                $parent_exist = 1;
                            }
                        }
                    }
                    if (!empty($Patients_ch) && $ag_checked == 0) {
                        $Patients_ch1 = $this->ClinicUser->find('first', array(
                            'joins' => array(
                                array(
                                    'table' => 'users',
                                    'alias' => 'user',
                                    'type' => 'INNER',
                                    'conditions' => array(
                                        'user.id = ClinicUser.user_id'
                                    )
                                )),
                            'conditions' => array('user.parents_email' => $data['email'],
                                'ClinicUser.card_number !=' => $data['card'],
                                'ClinicUser.clinic_id' => $sessionpatient['clinic_id']
                            )
                        ));

                        if (!empty($Patients_ch1)) {
                            $parent_exist = 1;
                        }
                    }
                    if (isset($years_chd) && $years_chd > 18) {
                        $this->Session->setFlash(__('Email already exists. Use different email id.'));
                        return $this->redirect('/rewards/login/');
                    } else if (isset($parent_exist) && $parent_exist == 1) {
                        $this->Session->setFlash(__('Email already exists. Use different email id.'));
                        return $this->redirect('/rewards/login/');
                    } else if (isset($child_exist) && $child_exist == 1) {
                        $this->Session->setFlash(__('Additional Email already exists. Use diffrent email id.'));
                        return $this->redirect('/rewards/login/');
                    } else {
                        //check patient already have in another clinic..
                        $users_field = $this->User->find('all', array(
                            'joins' => array(
                                array(
                                    'table' => 'clinic_users',
                                    'alias' => 'clinic_users',
                                    'type' => 'INNER',
                                    'conditions' => array(
                                        'clinic_users.user_id = User.id'
                                    )
                                )
                            ),
                            'conditions' => array(
                                'clinic_users.clinic_id !=' => $sessionpatient['clinic_id'],
                                'User.email' => $data['email'],
                                'User.blocked !=' => 1
                            ),
                            'fields' => array('User.email', 'User.id', 'User.custom_date', 'clinic_users.clinic_id')
                        ));
                        //code end here

                        if (count($users_field) > 0) {
                            $email_chk = '';
                            $use_name = '';
                            foreach ($users_field as $uf) {
                                $date1_chk = $uf['User']['custom_date'];
                                $date1_chk = date('Y-m-d', strtotime('+4 days', strtotime($date1_chk)));
                                $date2_chk = date('Y-m-d');
                                $diff_chk = abs(strtotime($date2_chk) - strtotime($date1_chk));
                                $years_chk = floor($diff_chk / (365 * 60 * 60 * 24));
                                if ($years_chk > 12) {
                                    $email_chk = $uf['User']['email'];
                                    $use_name = $uf['User']['first_name'];
                                }
                            }
                            $email = $email_chk;

                            $linkstr = "";
                            $link = str_replace('[link_url]', '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/linkwithcard/" . base64_encode($email) . "/" . base64_encode($data['card']) . '">Link Url</a>', $template_array['content']);
                            $link1 = str_replace('[username]', $use_name, $link);
                            $template_array = $this->Api->get_template(25);
                            $sub = str_replace('[card_number]', $data['card'], $template_array['subject']);
                            $Email = new CakeEmail(MAILTYPE);
                            $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
                            $Email->to($email);
                            $Email->subject($sub)
                                    ->template('buzzydocother')
                                    ->emailFormat('html');
                            $Email->viewVars(array('msg' => $link1
                            ));
                            $Email->send();

                            $this->Session->setFlash(__("Check your email for linking."));
                            return $this->redirect('/rewards/login/');
                        } else {
                            $is_fb = 1;
                            $fb_id = $data['facebook_id'];
                            $new_password = dechex(time()) . mt_rand(0, 100000);
                            $data['new_password'] = $new_password;
                            $Patients_array['User'] = array(
                                'custom_date' => $data['custom_date'],
                                'email' => strtolower($data['email']),
                                'first_name' => $data['first_name'],
                                'last_name' => $data['last_name'],
                                'customer_password' => md5($data['new_password']),
                                'password' => $data['new_password'],
                                'points' => 0,
                                'enrollment_stamp' => date('Y-m-d H:i:s'),
                                'facebook_id' => $fb_id,
                                'is_facebook' => $is_fb,
                                'status' => 1,
                                'is_verified' => 1
                            );
                            $this->User->create();
                            $this->User->save($Patients_array);
                            $user_id = $this->User->getLastInsertId();
                            $Patients_CU_array['ClinicUser'] = array('clinic_id' => $sessionpatient['clinic_id'],
                                'user_id' => $user_id,
                                'card_number' => $data['card'],
                                'facebook_like_status' => 0
                            );
                            $this->ClinicUser->create();
                            $this->ClinicUser->save($Patients_CU_array);
                            $this->CardNumber->query("UPDATE `card_numbers` SET `status` = 2  WHERE `clinic_id` =" . $sessionpatient['clinic_id'] . " and card_number='" . $data['card'] . "'");
                            foreach ($sessionpatient['ProFieldGlobal'] as $val) {

                                if (isset($data[$val['ProfileField']['profile_field']])) {
                                    $pr_val = $data[$val['ProfileField']['profile_field']];
                                } else {
                                    $pr_val = '';
                                }
                                $records_pf_vl = array(
                                    "ProfileFieldUser" => array(
                                        "user_id" => $user_id,
                                        "profilefield_id" => $val['ProfileField']['id'],
                                        "value" => $pr_val,
                                        "clinic_id" => 0
                                    )
                                );
                                $this->ProfileFieldUser->create();
                                $this->ProfileFieldUser->save($records_pf_vl);
                            }

                            $Email = new CakeEmail(MAILTYPE);
                            $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
                            $Email->to($data['email']);
                            $template_array = $this->Api->get_template(4);
                            $link = str_replace('[first_name]', $data['first_name'], $template_array['content']);
                            $link1 = str_replace('[username]', $data['card'], $link);
                            $link2 = str_replace('[password]', $data['new_password'], $link1);
                            $link3 = str_replace('[click_here]', '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . '">Click Here</a>', $link2);
                            $sub = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $template_array['subject']);
                            $Email->subject($sub)
                                    ->template('buzzydocother')
                                    ->emailFormat('html');
                            $Email->viewVars(array('msg' => $link3
                            ));
                            $Email->send();


                            $Patients_get = $this->ClinicUser->find('first', array(
                                'joins' => array(
                                    array(
                                        'table' => 'users',
                                        'alias' => 'user',
                                        'type' => 'INNER',
                                        'conditions' => array(
                                            'user.id = ClinicUser.user_id'
                                        )
                                    )),
                                'conditions' => array(
                                    'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                                    'ClinicUser.user_id' => $user_id,
                                    'user.email' => $data['email'],
                                    'user.is_facebook' => 1,
                                    'user.is_verified' => 1
                                ),
                                'fields' => array('ClinicUser.*', 'user.*')
                            ));

                            if (isset($Patients_get) && !empty($Patients_get)) {
                                if (isset($sessionpatient['api_user'])) {
                                    $this->loadModel('user');
                                    $Patients = $this->user->find('first', array('conditions' => array('user.id' => $Patients_get['ClinicUser']['user_id'])));
                                    if ($Patients['user']['is_verified'] == 1) {

                                        foreach ($Patients['Clinic'] as $clinic) {
                                            if ($clinic['id'] == $sessionpatient['clinic_id']) {
                                                $userglobalval = $clinic['ClinicUser'];
                                            }
                                        }
                                        $pfieldarray = array();
                                        foreach ($Patients['ProfileField'] as $ProfileField) {
                                            if ($ProfileField['profile_field'] == 'gender') {
                                                $ProfileFieldval = $ProfileField['ProfileFieldUser']['value'];
                                            }
                                            if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                                                $pfieldarray[] = $ProfileField;
                                            }
                                        }
                                        $Patients['ProfileField'] = $pfieldarray;
                                        $Reward = $this->Reward->find('all', array('conditions' => array('Reward.clinic_id' => $sessionpatient['clinic_id'], 'Reward.points !=' => '', 'Reward.description !=' => '')));
                                        $this->Session->write('patient.Reward', $Reward);
                                        $this->Session->write('patient.var.patient_name', $Patients_get['ClinicUser']['card_number']);
                                        $this->Session->write('patient.var.patient_password', $Patients_get['user']['password']);
                                        $this->Session->write('patient.customer_info', $Patients);
                                        $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                                        if ($sessionpatient['is_buzzydoc'] == 1) {
                                            return $this->redirect(Buzzy_Name . 'buzzydoc/login/' . base64_encode($Patients['user']['id']));
                                        } else {
                                            if ($Patients['user']['first_name'] == '' || $Patients['user']['last_name'] == '' || $Patients['user']['email'] == '' || $ProfileFieldval == '' || $Patients['user']['custom_date'] == '') {
                                                $this->Session->setFlash(__('Please fill in the mandatory fields before proceeding'));
                                                return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                                            } else {
                                                return $this->redirect(array('controller' => 'rewards', 'action' => 'home'));
                                            }
                                        }
                                    } else {
                                        $this->Session->setFlash(__("Waiting on parent's email confirmation."));
                                    }
                                } else {
                                    $this->Session->setFlash(__('Clinic does not exists.'));
                                }
                            }
                        }
                    }
                } else {
                    $this->Session->setFlash(__('Patient Already exists.'));
                    return $this->redirect('/rewards/login/');
                }
            } else {
                if (!empty($user_profile['birthday'])) {
                    $custdt = date('Y-m-d', strtotime($user_profile['birthday']));
                } else {
                    $custdt = '0000-00-00';
                }
                $data = array('first_name' => $user_profile['first_name'], 'last_name' => $user_profile['last_name']
                    , 'email' => $user_profile['email'], 'customer_username' => $user_profile['username'], 'custom_date' => $custdt, 'gender' => $user_profile['gender'], 'facebook_id' => $user_profile['id'], 'is_facebook' => 1);
                $this->set('patientdetails', $data);

                $this->layout = "patientLayoutLogin";
                $this->render('/Rewards/facebooklogin');
            }
        }
    }

    /**
     * Defualt index page for this module.
     */
    public function index() {
        $this->layout = "patientLayout";
    }

    /**
     * Earn page for information how to earn points.
     */
    public function earn() {
        $this->layout = "patientLayout";

        $sessionstaff = $this->Session->read('patient');
        //getting the list of promotion for lite and full.
        if ($sessionstaff['is_lite'] == 1) {
            $options['conditions'] = array('Promotion.is_lite' => 1, 'Promotion.clinic_id' => $sessionstaff['clinic_id']);
            $options['order'] = array('Promotion.description' => 'asc');
            $promotion11 = $this->Promotion->find('all', $options);
        } else {
            $options['conditions'] = array('Promotion.description like' => '*%', 'Promotion.clinic_id' => $sessionstaff['clinic_id']);
            $options['order'] = array('Promotion.description' => 'asc');
            $promotion = $this->Promotion->find('all', $options);

            $prid = array();
            foreach ($promotion as $pr) {
                if ($pr['Promotion']['is_lite'] != 1) {
                    $prid[] = $pr['Promotion']['id'];
                }
            }
            $options1['conditions'] = array('NOT' => array('Promotion.id' => $prid), 'Promotion.clinic_id' => $sessionstaff['clinic_id']);
            $options1['order'] = array('Promotion.value' => 'asc');
            $promotion1 = $this->Promotion->find('all', $options1);
            $$promotion2 = array();
            foreach ($promotion1 as $pr1) {
                if ($pr1['Promotion']['is_lite'] != 1) {
                    $promotion2[] = $pr1;
                }
            }
            $promotion22 = array_merge_recursive($promotion, $promotion2);
            $promotion11 = array_unique($promotion22, SORT_REGULAR);
        }
        $this->set("Promotions", $promotion11);
        //getting the list of documents.
        $options1['conditions'] = array('Document.clinic_id' => $sessionstaff['clinic_id']);
        $options1['order'] = array('Document.title' => 'desc');
        $Documents = $this->Document->find('all', $options1);

        $this->set('Documents', $Documents);
    }

    /**
     * @depricated
     */
    public function documents() {
        $this->layout = "patientLayout";
        $sessionstaff = $this->Session->read('patient');
        $this->paginate = array(
            'fields' => array('Document.id', 'Document.document', 'Document.title'),
            'conditions' => array('Document.clinic_id' => $sessionstaff['clinic_id']),
            'limit' => 10,
            'order' => array('Document.title' => 'desc'),
            'group' => array('Document.id')
        );
        $Documents = $this->paginate('Document');

        $this->set('Documents', $Documents);
    }

    /**
     * Refer to fiends and family.
     * @return type
     */
    public function refer() {
        $this->layout = "patientLayout";
        $sessionpatient = $this->Session->read('patient');
        //getting the industry type for practice.
        $ind = $this->IndustryType->find('first', array('conditions' => array('IndustryType.id' => $sessionpatient['Themes']['industry_type'])));
        //Getting the all leads setting related to industry.
        $leads = $this->LeadLevel->find('all', array('conditions' => array('LeadLevel.industryId' => $sessionpatient['Themes']['industry_type'])));

        $ref_msg = json_decode($ind['IndustryType']['reffralmessages']);
        if ($ref_msg == '') {
            $rmsg = array();
        } else {
            $rmsg = $ref_msg;
        }
        $this->set('refer_msg', $rmsg);
        $this->set('leads', $leads);
        $admin_set = $this->AdminSetting->find('first', array('conditions' => array('AdminSetting.clinic_id' => $sessionpatient['clinic_id'], 'AdminSetting.setting_type' => 'leadpoints')));
        $this->set('admin_settings', $admin_set);
        //refer to friends.
        if ($this->request->is('post')) {
            $Refers_array['Refer'] = array('card_number' => $sessionpatient['customer_info']['ClinicUser']['card_number'], 'first_name' => $this->request->data['first_name'], 'last_name' => $this->request->data['last_name'], 'email' => $this->request->data['email'], 'message' => $this->request->data['message'], 'user_id' => $this->request->data['user_id'], 'clinic_id' => $sessionpatient['clinic_id'], 'status' => 'Pending', 'refdate' => date('Y-m-d H:i:s'));

            $this->Refer->create();
            $this->Refer->save($Refers_array);
            $ref_id = $this->Refer->getLastInsertId();
            $template_array_red = $this->Api->save_notification($Refers_array['Refer'],4,$ref_id);
            $refpromotion = $this->Refpromotion->find('all', array(
                'joins' => array(
                    array(
                        'table' => 'clinic_promotions',
                        'alias' => 'ClinicPromotion',
                        'type' => 'INNER',
                        'conditions' => array(
                            'ClinicPromotion.promotion_id = Refpromotion.id'
                        )
                    )),
                'conditions' => array(
                    'ClinicPromotion.clinic_id' => $sessionpatient['clinic_id']
                )
            ));
            $template_array = $this->Api->get_template(9);

            $Email = new CakeEmail(MAILTYPE);
            if (empty($sessionpatient['customer_info']['user']['email'])) {
                $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
            } else {
                $Email->from(array($sessionpatient['customer_info']['user']['email'] => 'BuzzyDoc'));
            }
            $Email->to($this->request->data['email']);
            $Email->subject($template_array['subject'])
                    ->template('buzzydocother')
                    ->emailFormat('html');
            $promotion = '<br>';
            if (!empty($refpromotion)) {
                foreach ($refpromotion as $refp) {
                    $promotion.= $refp['Refpromotion']['promotion_area'] . '<br>';
                }
            }
            $link = str_replace('[accept_link]', "<a href='" . rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/lead/" . base64_encode($ref_id) . "' style='background: none repeat scroll 0 0 #2FB888;color: #FFFFFF;display: block;margin: 10px 0 0;padding: 10px;text-decoration: none;width: 42%;'>SURE I'LL ACCEPT THIS REFERRAL!</a>" . $promotion, $template_array['content']);
            $link1 = str_replace('[description]', $this->request->data['message'], $link);
            $link2 = str_replace('[username]', $this->request->data['first_name'], $link1);
            $link3 = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $link2);
            $Email->viewVars(array('msg' => $link3));
            $Email->send();
            return $this->redirect('/rewards/profile/#lirefer');
        }
    }

    /**
     * Getting the referral email preview.
     */
    public function referpreview() {
        $sessionpatient = $this->Session->read('patient');
        $refpromotion = $this->Refpromotion->find('all', array(
            'joins' => array(
                array(
                    'table' => 'clinic_promotions',
                    'alias' => 'ClinicPromotion',
                    'type' => 'INNER',
                    'conditions' => array(
                        'ClinicPromotion.promotion_id = Refpromotion.id'
                    )
                )),
            'conditions' => array(
                'ClinicPromotion.clinic_id' => $sessionpatient['clinic_id']
            )
        ));

        $promotion = '<br>';
        if (!empty($refpromotion)) {
            foreach ($refpromotion as $refp) {
                $promotion.= $refp['Refpromotion']['promotion_area'] . '<br>';
            }
        }

        $var = '<a class="close closebtn" onclick="close_form();" style="background: none repeat scroll 0 0 #FFFFFF;color:#000000;height: auto;padding: 5px 20px 5px 10px;right: 0;top: 0;">&times;</a>   
	<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #FFFFFF;">
		<tr>
			<td>
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td background="' . CDN . 'img/header-bg2.jpg" bgcolor="#778cab" valign="top" style="background-size:cover; background-position:top;">
							<table class="table600" width="600" border="0" align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" >
                                        <img style="display:block; line-height:0px; font-size:0px; border:0px;margin-right: 90px;" src="' . S3Path . $sessionpatient['Themes']['patient_logo_url'] . '" width="246" height="88" alt="logo" />
                                    </td>
                                </tr>
								<tr>
									<td height="20"></td>
								</tr>
								<tr>
									<td height="30"></td>
								</tr>
								<tr>
									<td align="center" style=" height: 80px; font-family: Open Sans, Arial, sans-serif; font-size:14px; color:#ffffff; line-height:24px;">
									</td>
								</tr>
								<tr>
									<td height="50"></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table class="table600" width="600" border="0" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td height="50"></td>
					</tr>
					<tr>
						<td align="center" style="font-family: Open Sans, Arial, sans-serif; font-size:18px; font-weight: bold; color:#3498db;">' . $this->request->data['message'] . '.</td>
					</tr>
					<tr>
						<td height="10"></td>
					</tr>
					<tr>
	<td align="center" style="font-family: Open Sans, Arial, sans-serif; font-size:13px; color:#7f8c8d; line-height:24px;">
        <a href="javascript:void(0)" style="background: none repeat scroll 0 0 #2FB888;
    color: #FFFFFF;
    display: block;
    margin: 10px 0 0;
    padding: 10px;
    text-decoration: none;
    width: 42%;">SURE I\'LL ACCEPT THIS REFERRAL!</a> <br />Click link to accept your referral and have someone from the practice reach out to you when you\'re ready ' . $promotion . '</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td height="50"></td>
					</tr>
				</table>
			</td>
		</tr>
        <tr>
            <td>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="50"></td>
                    </tr>
                    <tr>
                        <td bgcolor="#333333">
                            <table class="table600" width="600" border="0" align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <table class="table3-3" width="183" border="0" align="left" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td height="16" style="padding-top: 10px;">
                                                    <a href="#"><img src="' . CDN . 'img/lamparski/lamparski_footer_image" alt="logo" title="logo"/> </a>
                                                </td>
                                            </tr>
                                        </table>
<table class="table3-3" width="392" border="0" align="right" cellpadding="0" cellspacing="0">       
<tr>
    <td  align="right" style=" padding-top: 5px;">
        <table>
            <tr>
                <td>
                    <span style="font-family: Open Sans, Arial, sans-serif; font-size:13px; text-align: right; color:#fff; line-height:28px;">Follow Us &nbsp;</span>
                </td>';
        if (isset($sessionpatient['Themes']['twitter_url']) && $sessionpatient['Themes']['twitter_url'] != '') {
            $var.='<td><a href="' . $sessionpatient['Themes']['twitter_url'] . '" target="_blank"><img src="' . CDN . 'img/reward_imges/twitter.png" height=""/> </a></td>';
        }
        if (isset($sessionpatient['Themes']['facebook_url']) && $sessionpatient['Themes']['facebook_url'] != '') {
            $var.='<td><a href="' . $sessionpatient['Themes']['facebook_url'] . '" target="_blank"><img src="' . CDN . 'img/reward_imges/facebook.png" height=""/> </a></td>';
        }
        if (isset($sessionpatient['Themes']['google_url']) && $sessionpatient['Themes']['google_url'] != '') {
            $var.=' <td><a href="' . $sessionpatient['Themes']['google_url'] . '" target="_blank"><img src="' . CDN . 'img/reward_imges/googleplus.png" height=""/> </a></td>';
        }
        if (isset($sessionpatient['Themes']['instagram_url']) && $sessionpatient['Themes']['instagram_url'] != '') {
            $var.='<td><a href="' . $sessionpatient['Themes']['instagram_url'] . '" target="_blank"><img src="' . CDN . 'img/reward_imges/instagram.png" height=""/> </a></td>';
        }
        if (isset($sessionpatient['Themes']['pintrest_url']) && $sessionpatient['Themes']['pintrest_url'] != '') {
            $var.='<td><a href="' . $sessionpatient['Themes']['pintrest_url'] . '" target="_blank"><img src="' . CDN . 'img/reward_imges/pinterest.png" height=""/> </a></td>';
        }
        if (isset($sessionpatient['Themes']['yelp_url']) && $sessionpatient['Themes']['yelp_url'] != '') {
            $var.='<td><a href="' . $sessionpatient['Themes']['yelp_url'] . '" target="_blank"><img src="' . CDN . 'img/reward_imges/yelp.png" height=""/></a></td>';
        } if (isset($sessionpatient['Themes']['youtube_url']) && $sessionpatient['Themes']['youtube_url'] != '') {
            $var.='<td><a href="' . $sessionpatient['Themes']['youtube_url'] . '" target="_blank"><img src="' . CDN . 'img/reward_imges/you-tube.png" height=""/></a></td>';
        } if (isset($sessionpatient['Themes']['healthgrade_url']) && $sessionpatient['Themes']['healthgrade_url'] != '') {
            $var.=' <td><a href="' . $sessionpatient['Themes']['healthgrade_url'] . '" target="_blank"><img src="' . CDN . 'img/reward_imges/HealthGrades.png" height=""/> </a></td>';
        }
        $var.='</tr>
        </table>
    </td>
</tr>
                                            <tr>
                                                <td class="footer-link" style="font-family: Open Sans, Arial, sans-serif; font-size:13px; text-align: right; color:#fff; line-height:28px;">
                                                        <span >Support: help@buzzydoc.com<br>
                                                        (888) 696-4753<br>
                                                        Your information is safe </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="20"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>   
	</table>';
        echo $var;
        die;
    }

    /**
     * Getting the message accouring to lead levels.
     */
    public function getmsg() {
        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');

        $ind = $this->IndustryType->find('first', array('conditions' => array('IndustryType.id' => $sessionpatient['Themes']['industry_type'])));
        $ref_msg = json_decode($ind['IndustryType']['reffralmessages']);
        $fname = 'reffralmessage' . $_POST['id'];
        echo $ref_msg->$fname;
        exit;
    }

    /**
     * Resend referral email to friends.
     */
    public function resendrefer() {

        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');
        $refer = $this->Refer->find('first', array('conditions' => array('Refer.id' => $_POST['ref_id'])));

        if (!empty($refer)) {
            $template_array = $this->Api->get_template(9);
            $link = str_replace('[accept_link]', "<a href='" . rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/lead/" . base64_encode($refer['Refer']['id']) . "' style='background: none repeat scroll 0 0 #2FB888;color: #FFFFFF;display: block;margin: 10px 0 0;padding: 10px;text-decoration: none;width: 42%;'>SURE I'LL ACCEPT THIS REFERRAL!</a>", $template_array['content']);
            $link1 = str_replace('[description]', $refer['Refer']['message'], $link);
            $link2 = str_replace('[username]', $refer['Refer']['first_name'], $link1);
            $link3 = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $link2);
            $Email = new CakeEmail(MAILTYPE);
            if (empty($sessionpatient['customer_info']['user']['email'])) {
                $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
            } else {
                $Email->from(array($sessionpatient['customer_info']['user']['email'] => 'BuzzyDoc'));
            }
            $Email->to($refer['Refer']['email']);
            $Email->subject($template_array['subject'])
                    ->template('buzzydocother')
                    ->emailFormat('html');
            $Email->viewVars(array('msg' => $link3));
            $Email->send();
            echo 1;
        } else {
            echo 0;
        }
        die;
    }

    /**
     * New lead generated and mail send to staff user.
     * @param type $id
     * @return type
     */
    public function lead($id) {
        $this->layout = "patientLayoutLogin";
        $sessionpatient = $this->Session->read('patient');
        $refer = $this->Refer->find('first', array('conditions' => array('Refer.id' => base64_decode($id))));
        $this->set('refers', $refer);
        $user = $this->User->find('first', array('conditions' => array('User.id' => $refer['Refer']['user_id'])));

        $this->set('referred_by', $user['User']['first_name'] . ' ' . $user['User']['last_name']);
        if (isset($this->request->data['lead_add'])) {

            $referget = $this->Refer->find('first', array('conditions' => array('Refer.id' => $this->request->data['id'])));
            if ($referget['Refer']['status'] == 'Pending') {
                $agree = 0;
                if (isset($this->request->data['agree'])) {
                    $agree = 1;
                }

                $this->Refer->query("UPDATE `refers` SET `status` = 'Accepted', first_name='" . $this->request->data['first_name'] . "', last_name='" . $this->request->data['last_name'] . "' , email='" . $this->request->data['email'] . "', phone='" . $this->request->data['phone'] . "' , prefer_time='" . $this->request->data['pref_time'] . "' ,agree='" . $agree . "' WHERE `id` = " . $this->request->data['id']);
                $staffs = $this->Staff->find('all', array('conditions' => array('OR' => array('Staff.staff_role' => 'A', 'Staff.staff_role' => 'Administrator'), 'Staff.clinic_id' => $sessionpatient['clinic_id'], 'Staff.staff_email !=' => '')));
                foreach ($staffs as $staff) {
                    $template_array = $this->Api->get_template(26);
                    $link = str_replace('[click_here]', '<a href="' . rtrim($sessionpatient['Themes']['staff_url'], '/') . '">Click Here</a>', $template_array['content']);
                    $link1 = str_replace('[staff_name]', $staff['Staff']['staff_id'], $link);
                    $Email = new CakeEmail(MAILTYPE);
                    $Email->from(array($this->request->data['email'] => 'BuzzyDoc'));
                    $Email->to($staff['Staff']['staff_email']);
                    $Email->subject($template_array['subject'])
                            ->template('buzzydocother')
                            ->emailFormat('html');
                    $Email->viewVars(array('msg' => $link1
                    ));
                    $Email->send();
                }
                $this->Session->setFlash(__('You\'ve successfully accepted the referral request.'));
                return $this->redirect('/rewards/lead/' . $id);
            } else {
                $this->Session->setFlash(__('Already Accepted the referral request.'));
                return $this->redirect('/rewards/lead/' . $id);
            }
        }
    }

    /**
     * Logout from system.
     * @return type
     */
    public function logout() {
        $this->Session->destroy();
        $this->Session->delete('patient');
        return $this->redirect(array('controller' => 'rewards', 'action' => 'login'));
    }

    /**
     * Remove added rewards from wish list.
     */
    public function removewishList() {
        $sessionpatient = $this->Session->read('patient');
        if ((isset($this->request->data['wishlist_id'])) && (array_key_exists("wishlist_id", $this->request->data))) {
            $resutl = $this->WishList->deleteAll(array('WishList.reward_id' => $this->request->data['wishlist_id'], 'WishList.user_id' => $sessionpatient['customer_info']['user']['id'], 'WishList.clinic_id' => $sessionpatient['clinic_id'], false));
            if ($resutl) {
                $options['fields'] = array('WishList.reward_id');
                $options['conditions'] = array('WishList.user_id' => $sessionpatient['customer_info']['user']['id'], 'WishList.clinic_id' => $sessionpatient['clinic_id']);
                $WishLists = $this->WishList->find('all', $options);

                $wishlist = array();
                foreach ($WishLists as $wl) {
                    $wishlist[] = $wl['WishList']['reward_id'];
                }
                $wish = array();
                foreach ($sessionpatient['Reward'] as $reward) {
                    if (in_array($reward['Reward']['id'], $wishlist)) {
                        $wish[] = $reward;
                    }
                }

                $xml = "";
                if (isset($sessionpatient['customer_info'])) {
                    if ($sessionpatient['is_buzzydoc'] == 1) {
                        $current_balance = $sessionpatient['customer_info']['user']['points'];
                    } else {
                        $current_balance = $sessionpatient['customer_info']['ClinicUser']['local_points'];
                    }
                } else {
                    $current_balance = '0';
                }

                foreach ($wish as $wishlist) {
                    $xml .="<div style=\"position:relative;\" class=\"col-lg-4 col-md-4 col-sm-6 col-xs-6 profile clearfix\">";
                    $xml .="<div class=\"remove\" onclick=\"removeWishList(" . $wishlist['Reward']['id'] . ")\">";
                    $xml .="<img src='" . CDN . "img/reward_imges/remove_btn.png' class='hand-icon'>";
                    $xml .="</div>";

                    $need_more = $wishlist['Reward']['points'] - $current_balance;
                    if (intval($current_balance) >= intval($wishlist['Reward']['points'])) {
                        if ($sessionpatient['is_mobile'] == 0) {
                            $xml .="<div class=\"col-lg-4 col-md-4 col-sm-4 col-xs-12  productBoxSM l-margin box_wid\" >";
                        } else {
                            $xml .="<div class=\"col-lg-4 col-md-4 col-sm-4 col-xs-12  productBoxSM l-margin\" >";
                        }
                    } else {
                        $xml .="<div class=\"col-lg-4 col-md-4 col-sm-4 col-xs-12  productBoxSM l-margin\">";
                    }

                    $uploadFolder = "rewards/" . $sessionpatient['api_user'];
                    $uploadPath = WWW_ROOT . 'img/' . $uploadFolder;

                    if (strlen($wishlist['Reward']['description']) > 40) {
                        $dis = substr($wishlist['Reward']['description'], 0, 40) . '...';
                    } else {
                        $dis = $wishlist['Reward']['description'];
                    }
                    $xml .="<p>" . $dis . "</p>";


                    $xml .="<img src='" . $wishlist['Reward']['imagepath'] . "' width=\"175\" height=\"117\">";

                    $xml .="<h3>" . $wishlist['Reward']['points'] . "points<br>";

                    if ($need_more > 0) {
                        $xml .="<span>You need " . $need_more . " more points.</span>";
                    } else {
                        if ($sessionpatient['is_mobile'] == 0) {
                            $xml .="<span>Bravo! <a class=\"hand-icon\" onclick=\"lightbox(" . $wishlist['Reward']['id'] . ");\">Click to redeem now</a></span>";
                        } else {
                            $xml .="<span>Bravo! <a class=\"hand-icon\" onClick=\"document.reward_form_" . $wishlist['Reward']['id'] . ".submit();\">Click to redeem now</a></span>";
                        }
                    }
                    $xml .="<span class=\"headTopCorner\"></span>";
                    $xml .="<span class=\"headrightcorner\"></span>";
                    $xml .="</h3>";
                    $xml .="</div>";
                    $xml .="</div>";
                }
                if (count($wish) < 1) {

                    $xml .="<div class=\"col-lg-12 settinproducBox l-margin no-item\">";
                    $xml .="<p>No items in wishlist</p>";
                    $xml .="</div>";
                }

                echo $xml;
                exit;
            }
        }

        exit;
    }

    /**
     * Getting the all profile details like (Notification setting,refer friend list,wish list,profile details,order status,change password etc.)
     * @return type
     */
    public function profile() {
        $sessionpatient = $this->Session->read('patient');
        //Getting the list of wishList monthly vies.
        if ($this->request->is('post') && $this->request->data['action'] != 'month_change') {
            if ($this->request->is('post') && $this->request->data['action'] == 'remove_wishlist') {
                $this->WishLists->deleteAll(array('WishLists.reward_id' => $this->request->data['which_reward_id'], 'WishLists.customer_card' => $sessionpatient['customer_info']['customer'][0]['card_number'], 'WishLists.client_id' => $sessionpatient['api_user'], false));
                return $this->redirect(array('controller' => 'rewards', 'action' => 'profile'));
            }
            //Update profile details,profile image.
            if ($this->request->is('post') && $this->request->data['action'] == 'record_myinfo') {
                foreach ($this->request->data as $allfield1 => $allfieldval1) {
                    $checkfield = explode('_', $allfield1);
                    if ($checkfield[0] == 'other') {

                        $findfield = str_replace('other_', '', $allfield1);
                        $newarray[$findfield] = $allfieldval1;
                        unset($this->request->data[$allfield1]);
                    }
                }

                foreach ($this->request->data as $allfield => $allfieldval) {
                    if (is_array($allfieldval) && $allfield != 'profile_image') {
                        $this->request->data[$allfield] = implode(',', $allfieldval);
                    } else {
                        $this->request->data[$allfield] = $allfieldval;
                    }
                    if (isset($newarray[$allfield])) {

                        $this->request->data[$allfield] = $this->request->data[$allfield] . '###' . $newarray[$allfield];
                    }
                }

                $this->request->data['custom_date'] = $this->request->data['date_year'] . '-' . $this->request->data['date_month'] . '-' . $this->request->data['date_day'];
                if (isset($this->request->data['parents_email'])) {
                    $this->request->data['email'] = $this->request->data['parents_email'];
                    $this->request->data['parents_email'] = $this->request->data['aemail'];
                } else {
                    $this->request->data['parents_email'] = '';
                }


                if ($this->data) {
                    $image = $this->data['profile_image'];
                    if ($image['name'] != '') {
                        //allowed image types
                        $imageTypes = array("image/gif", "image/jpeg", "image/png", "image/GIF", "image/JPEG", "image/PNG");
                        //upload folder - make sure to create one in webroot
                        $uploadFolder = "profile";
                        //full path to upload folder
                        $uploadPath = WWW_ROOT . 'img/' . $uploadFolder;
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0777, true);
                            chmod($uploadPath, 0777);
                        }

                        //check if image type fits one of allowed types
                        if (in_array($image['type'], $imageTypes)) {
                            //check if there wasn't errors uploading file on serwer
                            if ($image['error'] == 0) {
                                //image file name
                                $imageName = time() . '_' . $image['name'];
                                //check if file exists in upload folder
                                if (file_exists($uploadPath . '/' . $imageName)) {
                                    //create full filename with timestamp
                                    unlink($uploadPath . '/' . $imageName);
                                }
                                //create full path with image name
                                $full_image_path = $uploadPath . '/' . $imageName;
                                //upload image to upload folder
                                if (move_uploaded_file($image['tmp_name'], $full_image_path)) {

                                    $response = $this->CakeS3->putObject($full_image_path, 'img/' . $uploadFolder . '/' . $imageName, $this->CakeS3->permission('public_read_write'));


                                    if ($this->User->query("UPDATE `users` SET `custom_date` = '" . $this->request->data['custom_date'] . "',`email` = '" . $this->request->data['email'] . "',`parents_email` = '" . $this->request->data['parents_email'] . "',`last_name` = '" . $this->request->data['last_name'] . "',`first_name` = '" . $this->request->data['first_name'] . "',`profile_img_url` = 'img/" . $uploadFolder . "/" . $imageName . "' WHERE `id` =" . $this->request->data['id'])) {
                                        foreach ($sessionpatient['ProfileField'] as $val) {

                                            if (isset($this->request->data[$val['ProfileField']['profile_field']])) {
                                                $pr_val = $this->request->data[$val['ProfileField']['profile_field']];
                                            } else {
                                                $pr_val = '';
                                            }
                                            $records_pf_vl = array("ProfileFieldUser" => array("user_id" => $this->request->data['id'], "profilefield_id" => $val['ProfileField']['id'], "value" => $pr_val));
                                            $ProfileField_val = $this->ProfileFieldUser->query("select * from  `profile_field_users` where (clinic_id=" . $sessionpatient['clinic_id'] . " or clinic_id='0' or clinic_id='') and user_id=" . $this->request->data['id'] . " and profilefield_id=" . $val['ProfileField']['id']);

                                            if (empty($ProfileField_val)) {
                                                $this->ProfileFieldUser->create();
                                                $this->ProfileFieldUser->save($records_pf_vl);
                                            } else {
                                                $this->ProfileFieldUser->query("UPDATE `profile_field_users` SET `value` = '" . $pr_val . "' WHERE `profilefield_id` = " . $val['ProfileField']['id'] . " AND `user_id` =" . $this->request->data['id'] . " AND clinic_id=" . $val['ProfileField']['clinic_id']);
                                            }
                                        }

                                        $this->loadModel('user');
                                        $Patients = $this->user->find('first', array('conditions' => array('user.id' => $this->request->data['id'])));
                                        foreach ($Patients['Clinic'] as $clinic) {
                                            if ($clinic['id'] == $sessionpatient['clinic_id']) {
                                                $userglobalval = $clinic['ClinicUser'];
                                            }
                                        }
                                        $pfieldarray = array();
                                        foreach ($Patients['ProfileField'] as $ProfileField) {

                                            if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                                                $pfieldarray[] = $ProfileField;
                                            }
                                        }
                                        $Patients['ProfileField'] = $pfieldarray;
                                        $this->Session->write('patient.customer_info', $Patients);
                                        $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                                        if ($this->request->data['selfcheck'] == 'selfcheckin') {
                                            return $this->redirect(array('controller' => 'rewards', 'action' => 'checkprofilecompletion'));
                                        }
                                        $this->Session->setFlash(__('Profile updated successfully.'));
                                        return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                                    } else {
                                        $this->Session->setFlash(__('Profile not updated.'));
                                        return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                                    }
                                } else {
                                    $this->Session->setFlash('There was a problem uploading file. Please try again.');
                                    return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                                }
                            } else {
                                $this->Session->setFlash('Error uploading file.');
                                return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                            }
                        } else {
                            $this->Session->setFlash('Unacceptable file type');
                            return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                        }
                    } else {
                        $this->User->query("UPDATE `users` SET `custom_date` = '" . $this->request->data['custom_date'] . "',`email` = '" . strtolower($this->request->data['email']) . "',`parents_email` = '" . strtolower($this->request->data['parents_email']) . "',`last_name` = '" . $this->request->data['last_name'] . "',`first_name` = '" . $this->request->data['first_name'] . "' WHERE `id` =" . $this->request->data['id']);

                        foreach ($sessionpatient['ProfileField'] as $val) {

                            if (isset($this->request->data[$val['ProfileField']['profile_field']])) {
                                $pr_val = $this->request->data[$val['ProfileField']['profile_field']];
                            } else {
                                $pr_val = '';
                            }
                            $records_pf_vl = array("ProfileFieldUser" => array("user_id" => $this->request->data['id'], "profilefield_id" => $val['ProfileField']['id'], "value" => $pr_val, 'clinic_id' => $val['ProfileField']['clinic_id']));

                            $ProfileField_val = $this->ProfileFieldUser->query("select * from  `profile_field_users` where (clinic_id=" . $sessionpatient['clinic_id'] . " or clinic_id='0' or clinic_id='') and user_id=" . $this->request->data['id'] . " and profilefield_id=" . $val['ProfileField']['id']);

                            if (empty($ProfileField_val)) {
                                $this->ProfileFieldUser->create();
                                $this->ProfileFieldUser->save($records_pf_vl);
                            } else {
                                $this->ProfileFieldUser->query("UPDATE `profile_field_users` SET `value` = '" . $pr_val . "' WHERE `profilefield_id` = " . $val['ProfileField']['id'] . " AND `user_id` =" . $this->request->data['id'] . " AND clinic_id=" . $val['ProfileField']['clinic_id']);
                            }
                        }
                        $this->loadModel('user');
                        $Patients = $this->user->find('first', array('conditions' => array('user.id' => $this->request->data['id'])));
                        foreach ($Patients['Clinic'] as $clinic) {
                            if ($clinic['id'] == $sessionpatient['clinic_id']) {
                                $userglobalval = $clinic['ClinicUser'];
                            }
                        }
                        $pfieldarray = array();
                        foreach ($Patients['ProfileField'] as $ProfileField) {

                            if ($ProfileField['ProfileFieldUser']['clinic_id'] == $sessionpatient['clinic_id'] || $ProfileField['ProfileFieldUser']['clinic_id'] == 0) {

                                $pfieldarray[] = $ProfileField;
                            }
                        }
                        $Patients['ProfileField'] = $pfieldarray;
                        $this->Session->write('patient.customer_info', $Patients);
                        $this->Session->write('patient.customer_info.ClinicUser', $userglobalval);
                        if ($this->request->data['selfcheck'] == 'selfcheckin') {
                            return $this->redirect(array('controller' => 'rewards', 'action' => 'checkprofilecompletion'));
                        }
                        $this->Session->setFlash(__('Profile updated successfully.'));
                        return $this->redirect(array('controller' => 'rewards', 'action' => 'editprofile'));
                    }
                }
            }
            //Password changes for account.
            if ($this->request->is('post') && $this->request->data['action'] == 'passet') {

                $this->User->query("UPDATE `users` SET `customer_password` = md5('" . $this->request->data['new_password'] . "'),`password`='" . $this->request->data['new_password'] . "' WHERE `id` =" . $this->request->data['user_id']);
                $this->Session->setFlash(__('Password changed successfully.'));
                $this->Session->write('patient.var.patient_password', $this->request->data['new_password']);
                return $this->redirect(array('controller' => 'rewards', 'action' => 'profile'));
            }
            //Update notification settings.
            if ($this->request->is('post') && $this->request->data['action'] == 'notification') {
                if (isset($this->request->data['reward_challenges'])) {
                    $reward_challenges = 1;
                } else {
                    $reward_challenges = 0;
                }
                if (isset($this->request->data['order_status'])) {
                    $order_status = 1;
                } else {
                    $order_status = 0;
                }
                if (isset($this->request->data['earn_points'])) {
                    $earn_points = 1;
                } else {
                    $earn_points = 0;
                }
                if (isset($this->request->data['points_expire'])) {
                    $points_expire = 1;
                } else {
                    $points_expire = 0;
                }
                if ($this->request->data['id'] == '') {
                    $notification_array['Notification'] = array('reward_challenges' => $reward_challenges, 'order_status' => $order_status, 'earn_points' => $earn_points, 'points_expire' => $points_expire, 'user_id' => $sessionpatient['customer_info']['user']['id'], 'clinic_id' => $sessionpatient['clinic_id']);
                } else {
                    $notification_array['Notification'] = array('id' => $this->request->data['id'], 'reward_challenges' => $reward_challenges, 'order_status' => $order_status, 'earn_points' => $earn_points, 'points_expire' => $points_expire, 'user_id' => $sessionpatient['customer_info']['user']['id'], 'clinic_id' => $sessionpatient['clinic_id']);
                }
                $this->Notification->create();
                $this->Notification->save($notification_array);
                return $this->redirect('/rewards/profile/#linotification');
            }
        }
        $options['fields'] = array('WishList.reward_id');
        //getting the wish list.
        $options['conditions'] = array('WishList.user_id' => $sessionpatient['customer_info']['user']['id'], 'WishList.clinic_id' => $sessionpatient['clinic_id']);
        $WishLists = $this->WishList->find('all', $options);

        $wishlist = array();
        foreach ($WishLists as $wl) {
            $wishlist[] = $wl['WishList']['reward_id'];
        }
        $wish = array();
        foreach ($sessionpatient['Reward'] as $reward) {
            if (in_array($reward['Reward']['id'], $wishlist)) {
                $wish[] = $reward;
            }
        }
        $this->set('WishLists', $wish);
        //getting the order status monthly vies.
        if ($this->request->is('post') && $this->request->data['action'] == 'month_change') {
            $selectedmonth = $this->request->data['my_dropdown'];
        } else {
            $selectedmonth = date('n');
        }
        $curyear = date('Y');
        $curmonth = date('n');
        if ($selectedmonth > $curmonth) {
            $curyear--;
        }

        $start = $curyear . '-' . $selectedmonth . '-01';
        $end = $curyear . '-' . $selectedmonth . '-31';
        $options1['conditions'] = array('OR' => array('Transaction.clinic_id' => $sessionpatient['clinic_id'], 'Transaction.redeem_from' => $sessionpatient['clinic_id']), 'Transaction.activity_type' => 'Y', 'Transaction.user_id' => $sessionpatient['customer_info']['user']['id'], 'Transaction.date BETWEEN ? AND ?' => array($start, $end));
        $RedeemRewards = $this->Transaction->find('all', $options1);

        $this->set('selectedmonth', $selectedmonth);
        $this->set('RedeemRewards', $RedeemRewards);
        $options2['conditions'] = array('Notification.user_id' => $sessionpatient['customer_info']['user']['id'], 'Notification.clinic_id' => $sessionpatient['clinic_id']);
        $Notifications = $this->Notification->find('first', $options2);
        $this->set('Notifications', $Notifications);
        $options3['conditions'] = array('Refer.user_id' => $sessionpatient['customer_info']['user']['id'], 'Refer.clinic_id' => $sessionpatient['clinic_id'], 'Refer.email !=' => '');
        $Refers = $this->Refer->find('all', $options3);
        $this->set('Refers', $Refers);
        $this->layout = "patientLayout";
    }

    /**
     * Getting the profile details for Patient.
     * @param type $check
     */
    public function editprofile($check = '') {

        $this->layout = "patientLayout";
        $sessionpatient = $this->Session->read('patient');
        //transafer transaction from unreg to register
        $alltrans = $this->UnregTransaction->find('all', array(
            'conditions' => array(
                'UnregTransaction.user_id' => 0,
                'UnregTransaction.card_number' => $sessionpatient['customer_info']['ClinicUser']['card_number'],
                'UnregTransaction.clinic_id' => $sessionpatient['clinic_id']
            )
        ));

        foreach ($alltrans as $newtran) {
            $datatrans['user_id'] = $sessionpatient['customer_info']['user']['id'];
            $datatrans['staff_id'] = $newtran['UnregTransaction']['staff_id'];
            $datatrans['card_number'] = $sessionpatient['customer_info']['ClinicUser']['card_number'];
            $datatrans['first_name'] = $sessionpatient['customer_info']['user']['first_name'];
            $datatrans['last_name'] = $sessionpatient['customer_info']['user']['last_name'];
            $datatrans['promotion_id'] = $newtran['UnregTransaction']['promotion_id'];
            $datatrans['amount'] = $newtran['UnregTransaction']['amount'];
            $datatrans['activity_type'] = $newtran['UnregTransaction']['activity_type'];
            $datatrans['authorization'] = $newtran['UnregTransaction']['authorization'];
            $datatrans['clinic_id'] = $newtran['UnregTransaction']['clinic_id'];
            $datatrans['date'] = $newtran['UnregTransaction']['date'];
            $datatrans['status'] = $newtran['UnregTransaction']['status'];
            $datatrans['is_buzzydoc'] = 0;
            $this->Transaction->create();
            $this->Transaction->save($datatrans);
            $this->UnregTransaction->deleteAll(array('UnregTransaction.id' => $newtran['UnregTransaction']['id'], false));
        }
        if (!empty($alltrans)) {

            $allpoints = $this->Transaction->find('first', array(
                'conditions' => array(
                    'Transaction.user_id' => $sessionpatient['customer_info']['user']['id'],
                    'Transaction.clinic_id' => $sessionpatient['clinic_id']
                ),
                'fields' => array(
                    'SUM(Transaction.amount) AS points'
                ),
                'group' => array(
                    'Transaction.card_number'
            )));
            $newpoints = $allpoints[0]['points'];
            $this->ClinicUser->query("update clinic_users set local_points=" . $newpoints . " where user_id=" . $sessionpatient['customer_info']['user']['id'] . " and clinic_id=" . $sessionpatient['clinic_id']);
            $this->Session->write('patient.customer_info.ClinicUser.local_points', $newpoints);
        }

        //end code

        $state = $this->State->find('all');
        $this->set('states', $state);
        $state_val = '';
        foreach ($sessionpatient['customer_info']['ProfileField'] as $field) {
            if ($field['profile_field'] == 'state') {
                $state_val = $field['ProfileFieldUser']['value'];
            }
        }

        $options['joins'] = array(
            array('table' => 'states',
                'alias' => 'States',
                'type' => 'INNER',
                'conditions' => array(
                    'States.state_code = City.state_code',
                    'States.state = "' . $state_val . '"'
                )
            )
        );
        $options['fields'] = array('City.city');
        $cityresult = $this->City->find('all', $options);
        $this->set('city', $cityresult);
        $this->set('selfcheckin', $check);
        $this->render('editprofile');
    }

    /**
     * Forgot password action for patient.
     * @return type
     */
    public function forgotpassword() {
        $sessionpatient = $this->Session->read('patient');
        if ($this->request->is('post')) {

            $Patients = $this->User->find('first', array(
                'joins' => array(
                    array(
                        'table' => 'clinic_users',
                        'alias' => 'clinic_users',
                        'type' => 'INNER',
                        'conditions' => array(
                            'clinic_users.user_id = User.id'
                        )
                    )),
                'conditions' => array(
                    'clinic_users.clinic_id' => $sessionpatient['clinic_id'], 'clinic_users.card_number' => $this->request->data['card_number']
                )
            ));

            if (empty($Patients)) {
                $this->Session->setFlash(__('Card number does not exists.'));
            } else {
                $customer_email = $Patients['User']['email'];
                if ($customer_email != '') {
                    $new_password = dechex(time()) . mt_rand(0, 100000);
                    $this->User->query("UPDATE `users` SET `customer_password` = md5('" . $new_password . "'),`password`='" . $new_password . "' WHERE `id` = " . $Patients['User']['id']);
                    $template_array = $this->Api->get_template(27);
                    $link = str_replace('[password]', $new_password, $template_array['content']);
                    $link1 = str_replace('[username]', $Patients['User']['first_name'], $link);
                    $subject = str_replace('[emailid/staff_name]', $Patients['User']['email'], $template_array['subject']);
                    $Email = new CakeEmail(MAILTYPE);

                    $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));

                    $Email->to($customer_email);
                    $Email->subject($subject)
                            ->template('buzzydocother')
                            ->emailFormat('html');
                    $Email->viewVars(array('msg' => $link1
                    ));
                    $Email->send();
                    $this->Session->setFlash(__('Your password was sent to your email address.'));
                    return $this->redirect('/rewards/login/');
                } else {
                    $this->Session->setFlash(__('Your account doesn\'t have an email associated. Please contact the card issuing office'));
                }
            }
        }
        $this->layout = "patientLayoutLogin";
    }

    /**
     * Getting the list of Local and amazon rewards for patient.
     * @param type $id
     */
    public function redeemreward($id) {
        $sessionpatient = $this->Session->read('patient');
        if (isset($sessionpatient['Reward'])) {
            foreach ($sessionpatient['Reward'] as $reward) {
                if ($reward['Reward']['id'] == $id) {
                    $reward_array = $reward;
                }
            }
        } else {
            $options['joins'] = array(
                array('table' => 'clinics',
                    'alias' => 'Clinic',
                    'type' => 'INNER',
                    'conditions' => array(
                        'Clinic.id = Reward.clinic_id'
                    )
                )
            );
            $options['fields'] = array('Reward.points', 'Reward.description', 'Reward.category', 'Reward.imagepath', 'Clinic.api_user');
            $options['conditions'] = array('Reward.id' => $id);
            $RewardLists = $this->Reward->find('first', $options);
            $RewardLists['Reward']['api_user'] = $RewardLists['Clinic']['api_user'];
            $reward_array = $RewardLists;
        }
        $this->set('rewards', $reward_array);

        $sessionpatient = $this->Session->read('patient');
        if (isset($sessionpatient['customer_info'])) {

            $state = $this->State->find('all');
            $this->set('states', $state);
            foreach ($sessionpatient['customer_info']['ProfileField'] as $field) {
                if ($field['profile_field'] == 'state') {
                    $state = $field['ProfileFieldUser']['value'];
                }
            }
            $options3['joins'] = array(
                array('table' => 'states',
                    'alias' => 'States',
                    'type' => 'INNER',
                    'conditions' => array(
                        'States.state_code = City.state_code',
                        'States.state = "' . $state . '"'
                    )
                )
            );
        }
        $options3['fields'] = array('City.city');
        $cityresult = $this->City->find('all', $options3);
        $this->set('city', $cityresult);
        $this->layout = "patientLayout";
    }

    /**
     * Redeem local and amazon rewards for pratice by patient.
     * @return type
     */
    function redeem() {

        $sessionpatient = $this->Session->read('patient');
        if ($this->request->data['set_account_type'] == 1) {

            $current_balance = $sessionpatient['customer_info']['ClinicUser']['local_points'];
            $isbuzzy = 1;
        } else {
            $current_balance = $sessionpatient['customer_info']['ClinicUser']['local_points'];
            $isbuzzy = 0;
        }

        if ($current_balance >= $this->request->data['which_reward_level']) {
            $to = $this->request->data['email'];
            $redeem_reward_array['Transaction'] = array(
                'date' => date('Y-m-d H:i:s'),
                'user_id' => $this->request->data['id'],
                'authorization' => $this->request->data['which_reward_description'],
                'card_number' => $this->request->data['card_number'],
                'first_name' => $this->request->data['first_name'],
                'last_name' => $this->request->data['last_name'],
                'reward_id' => $this->request->data['which_reward_id'],
                'activity_type' => 'Y',
                'amount' => '-' . $this->request->data['which_reward_level'],
                'clinic_id' => $sessionpatient['clinic_id'],
                'status' => 'Redeemed',
                'is_buzzydoc' => $isbuzzy);

            $totalpoint = $sessionpatient['customer_info']['ClinicUser']['local_points'] - $this->request->data['which_reward_level'];

            $this->Transaction->create();
            if ($this->Transaction->save($redeem_reward_array)) {
                $transaction_id = $this->Transaction->getLastInsertId();
                $template_array_red = $this->Api->save_notification($redeem_reward_array['Transaction'],2,$transaction_id);
                $this->ClinicUser->query("UPDATE `clinic_users` SET `local_points` = '" . $totalpoint . "' WHERE `user_id` =" . $sessionpatient['customer_info']['user']['id'] . ' and clinic_id=' . $sessionpatient['clinic_id']);
                $this->Session->write('patient.customer_info.ClinicUser.local_points', $totalpoint);
                $this->loadModel('user');


                $Email = new CakeEmail(MAILTYPE);
                $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));
                $Email->to($sessionpatient['customer_info']['user']['email']);
                $Email->subject("Order Number : $transaction_id")
                        ->template('redeem_reward')
                        ->emailFormat('html');
                $Email->viewVars(array(
                    'msg' => '<p>Hi ' . $sessionpatient['customer_info']['user']['first_name'] . ' ' . $sessionpatient['customer_info']['user']['last_name'] . ',</p>',
                    'redeem_data' => $this->request->data
                ));
                $Email->send();
            }

            $this->Session->setFlash('Order is redeemed successfully');
            return $this->redirect('/rewards/home');
        } else {
            $this->Session->setFlash('Insufficient balance.');
            return $this->redirect('/rewards/redeemreward/' . $this->request->data['which_reward_id']);
        }
    }

    /**
     * Reward page with all local and amazon rewards.
     */
    public function reward() {
        $sessionpatient = $this->Session->read('patient');

        $this->layout = "patientLayout";

        if (isset($this->request->data['category'])) {

            $this->Session->write('patient.category_name', $this->request->data['category']);
        }
        $sessionpatient1 = $this->Session->read('patient');
        if (isset($sessionpatient1['category_name']) && $sessionpatient1['category_name'] != '') {
            $rdquery = "SELECT * FROM rewards WHERE clinic_id=" . $sessionpatient['clinic_id'] . " and points!='' and description !='' and category LIKE '%" . $sessionpatient1['category_name'] . "%' UNION SELECT rewards.* from rewards inner join clinic_rewards on clinic_rewards.reward_id=rewards.id WHERE clinic_rewards.clinic_id=" . $sessionpatient['clinic_id'] . " and rewards.points!='' and rewards.description !=''  and category LIKE '%" . $sessionpatient1['category_name'] . "%' order by points";
        } else {
            $rdquery = "SELECT * FROM rewards WHERE clinic_id=" . $sessionpatient['clinic_id'] . " and points!='' and description !='' UNION SELECT rewards.* from rewards inner join clinic_rewards on clinic_rewards.reward_id=rewards.id WHERE clinic_rewards.clinic_id=" . $sessionpatient['clinic_id'] . " and rewards.points!='' and rewards.description !=''  order by points";
        }

        $query_rd = $this->Reward->query($rdquery);

        function sortBySubkey(&$array, $subkey, $sortType = SORT_ASC) {
            foreach ($array as $subarray) {
                $keys[] = $subarray[0][$subkey];
            }
            array_multisort($keys, $sortType, $array);
        }

        sortBySubkey($query_rd, 'points');

        $n = 0;
        $rdar = array();
        foreach ($query_rd as $rd) {
            $rdar[$n]['Reward'] = $rd[0];
            $n++;
        }
        $this->Session->write('patient.Reward', $rdar);
        $rowCategory = $this->Category->find('all');
        $this->set("rowCategory", $rowCategory);

        $query = $this->Reward->query($rdquery);
        sortBySubkey($query, 'points');
        $this->set('Reward', $query);
    }

    /**
     * Getting the list of product and service who have activated the product and service access. 
     */
    public function productservice() {
        $sessionpatient = $this->Session->read('patient');
        if ($sessionpatient['is_buzzydoc'] == 1) {
            $getglbpoint = $this->Transaction->find('all', array(
                'conditions' => array(
                    'Transaction.user_id' => $sessionpatient['customer_info']['user']['id'],
                    'Transaction.is_buzzydoc' => 1,
                    'Transaction.clinic_id !=' => 0
                ),
                'group' => array('Transaction.clinic_id'),
                'fields' => array('sum(Transaction.amount) AS total', 'Transaction.clinic_id', 'Transaction.user_id')
            ));
            $perclinicbuzzpnt = array();
            foreach ($getglbpoint as $glbpt) {
                $getglberedem = $this->GlobalRedeem->find('first', array(
                    'conditions' => array(
                        'GlobalRedeem.clinic_id' => $glbpt['Transaction']['clinic_id'],
                        'GlobalRedeem.user_id' => $glbpt['Transaction']['user_id']
                    ),
                    'fields' => array('sum(GlobalRedeem.amount) AS total,GlobalRedeem.clinic_id')
                ));

                $paytoclinic = $glbpt[0]['total'];
                if ($getglberedem[0]['total'] != '') {
                    $perclinicbuzzpnt[$glbpt['Transaction']['clinic_id']] = $paytoclinic + $getglberedem[0]['total'];
                } else {
                    $perclinicbuzzpnt[$glbpt['Transaction']['clinic_id']] = $paytoclinic;
                }
            }
            $this->Session->write('patient.perclinicbuzzypoints', $perclinicbuzzpnt);
        }
        $this->layout = "patientLayout";

        $options['conditions'] = array('ProductService.clinic_id' => $sessionpatient['clinic_id'], 'ProductService.status' => 1);
        $ProductService = $this->ProductService->find('all', $options);

        function sortBySubkey(&$array, $subkey, $sortType = SORT_ASC) {
            foreach ($array as $subarray) {
                $keys[] = $subarray['ProductService'][$subkey];
            }
            array_multisort($keys, $sortType, $array);
        }

        sortBySubkey($ProductService, 'points');
        $this->set('ProductService', $ProductService);
    }

    /**
     * Redeem prodcut and service by patient at rewards site.
     */
    public function redeemlocproduct() {
        if ($this->request->data['user_id'] == '' && $this->request->data['product_id'] == '' && $this->request->data['points'] == '') {
            echo 2;
        } else {
            $checkpoint = $this->User->find('first', array('conditions' => array('User.id' => $this->request->data['user_id'])));
            if ($checkpoint['User']['points'] >= $this->request->data['points']) {
                $redeemres = $this->Transaction->query('CALL sp_redeem_points(' . $this->request->data['user_id'] . ',' . $this->request->data['product_id'] . ',' . $this->request->data['points'] . ',' . date("Y-m-d H:i:s") . ',"null")');

                $optionspro['conditions'] = array('ProductService.id' => $this->request->data['product_id']);
                $product = $this->ProductService->find('first', $optionspro);
                $optionscli['conditions'] = array('Clinic.id' => $product['ProductService']['clinic_id']);
                $fromclinic = $this->Clinic->find('first', $optionscli);
                if ($fromclinic['Clinic']['display_name'] == '') {
                    $clinicname = $fromclinic['Clinic']['api_user'];
                } else {
                    $clinicname = $fromclinic['Clinic']['display_name'];
                }
                $orderdetail = array('Order Number' => $redeemres[0]['redemption_details']['transaction_id'], 'Redeemed From' => $clinicname, 'Product/Service' => $product['ProductService']['title'], 'Points Redeemed' => $product['ProductService']['points']);
                $optionscgettrans['conditions'] = array('Transaction.id' => $redeemres[0]['redemption_details']['transaction_id']);
                $trandetails = $this->Transaction->find('first', $optionscgettrans);
                $template_array_red = $this->Api->save_notification($trandetails['Transaction'], 2, $redeemres[0]['redemption_details']['transaction_id']);
                if (DEBIT_FROM_BANK == 1) {
                    foreach ($redeemres as $dt) {
                        $paytoclinic = $dt['redemption_details']['points_to_be_deducted'];
                        if ($paytoclinic > 0) {
                            $options8['conditions'] = array('Staff.clinic_id' => $dt['redemption_details']['clinic_id'], 'Staff.staff_email !=' => '', 'Staff.redemption_mail' => 1);
                            $Staff = $this->Staff->find('first', $options8);
                            $stemail = '';
                            $stname = '';
                            if (!empty($Staff)) {
                                $stemail = $Staff['Staff']['staff_email'];
                                $stname = $Staff['Staff']['staff_id'];
                            }

                            if ($stemail == '') {
                                $options9['conditions'] = array('Staff.clinic_id' => $dt['redemption_details']['clinic_id'], 'Staff.staff_email !=' => '', 'Staff.staff_role' => 'Doctor');
                                $Staff1 = $this->Staff->find('first', $options9);
                                $stemail = $Staff1['Staff']['staff_email'];
                                $stname = $Staff1['Staff']['staff_id'];
                            }

                            if ($stemail == '') {
                                $options9['conditions'] = array('Staff.clinic_id' => $dt['redemption_details']['clinic_id'], 'Staff.staff_email !=' => '', 'OR' => array('Staff.staff_role' => 'Administrator', 'Staff.staff_role' => 'A'));
                                $Staff2 = $this->Staff->find('first', $options9);
                                $stemail = $Staff2['Staff']['staff_email'];
                                $stname = $Staff2['Staff']['staff_id'];
                            }
                            if ($stemail == '') {
                                $options10['conditions'] = array('Staff.clinic_id' => $dt['redemption_details']['clinic_id'], 'Staff.staff_email !=' => '', 'OR' => array('Staff.staff_role' => 'Manager', 'Staff.staff_role' => 'M'));
                                $Staff3 = $this->Staff->find('first', $options10);
                                $stemail = $Staff3['Staff']['staff_email'];
                                $stname = $Staff3['Staff']['staff_id'];
                            }


                            if ($stemail == '') {
                                $stemail = SUPER_ADMIN_EMAIL_STAFF;
                            }

                            $options['conditions'] = array('Clinic.id' => $dt['redemption_details']['clinic_id']);
                            $options['fields'] = array('Clinic.minimum_deposit', 'Clinic.api_user', 'Clinic.is_buzzydoc');
                            $minimumdeposit = $this->Clinic->find('first', $options);
                            $threshold = $minimumdeposit['Clinic']['minimum_deposit'] / 2;
                            $options4['conditions'] = array('Invoice.clinic_id' => $dt['redemption_details']['clinic_id']);
                            $options4['order'] = array('Invoice.payed_on desc');
                            $findlastpay = $this->Invoice->find('first', $options4);
                            $current_pay = $paytoclinic / 50;
                            $current_bal = $findlastpay['Invoice']['current_balance'] - $current_pay;
                            $ord_id = mt_rand(100000, 999999);

                            $Invoice_array['Invoice'] = array(
                                'clinic_id' => $dt['redemption_details']['clinic_id'],
                                'user_id' => $this->request->data['user_id'],
                                'amount' => $current_pay,
                                'invoice_id' => $ord_id,
                                'mode' => 'Debit',
                                'current_balance' => $current_bal,
                                'payed_on' => date('Y-m-d H:i:s'),
                                'status' => 1
                            );
                            $this->Invoice->create();
                            $this->Invoice->save($Invoice_array);
                            $reachthres = $current_bal - $threshold;
                            $template_array = $this->Api->get_template(12);
                            $link = str_replace('[staff_name]', $stname, $template_array['content']);
                            $link1 = str_replace('[reduced_amount]', $current_pay, $link);
                            $link2 = str_replace('[current_balance]', $current_bal, $link1);
                            $link3 = str_replace('[away_amount]', $reachthres, $link2);
                            $Email1 = new CakeEmail(MAILTYPE);

                            $Email1->from(array(SUPER_ADMIN_EMAIL_STAFF => 'BuzzyDoc'));

                            $Email1->to($stemail);
                            $Email1->subject($template_array['subject'])
                                    ->template('buzzydocother')
                                    ->emailFormat('html');

                            $Email1->viewVars(array('msg' => $link3
                            ));
                            $Email1->send();

                            if ($threshold >= $current_bal && $minimumdeposit['Clinic']['is_buzzydoc'] == 1) {
                                if ($current_bal <= 0) {
                                    $cb = explode('-', $current_bal);
                                    $amountpay = $cb[1] + $threshold + 1;
                                    $curnbal = $threshold + 1;
                                } else {
                                    $amountpay = $threshold;
                                    $curnbal = $threshold + $current_bal;
                                }
                                $transactionFee = .35 + $amountpay * (.75 / 100);

                                $totalcredit1 = $amountpay + $transactionFee;
                                $totalcredit = number_format($totalcredit1, 2, '.', '');
                                $paydet['conditions'] = array('PaymentDetail.clinic_id' => $dt['redemption_details']['clinic_id']);
                                $getpayemntdetails = $this->PaymentDetail->find('first', $paydet);
                                $transaction = new AuthorizeNetTransaction;
                                $transaction->amount = $totalcredit;
                                $transaction->customerProfileId = $getpayemntdetails['PaymentDetail']['customer_account_id'];
                                $transaction->customerPaymentProfileId = $getpayemntdetails['PaymentDetail']['customer_account_profile_id'];

                                $transaction_id = mt_rand(100000, 999999);
                                $lineItem = new AuthorizeNetLineItem;
                                $lineItem->itemId = $transaction_id;
                                $lineItem->name = $sku;
                                $lineItem->description = "Amazon gift card";
                                $lineItem->quantity = "1";
                                $lineItem->unitPrice = $amountpay;
                                $lineItem->taxable = "true";
                                $transaction->lineItems[] = $lineItem;
                                $request = new AuthorizeNetCIM;
                                $response = $request->createCustomerProfileTransaction("AuthCapture", $transaction);


                                if ($response->xml->messages->message->code == 'I00001') {
                                    $transactionResponse = $response->getTransactionResponse();
                                    $trnsid = $transactionResponse->transaction_id;
                                    $date2 = date("Y-m-d H:i:s");
                                    $Invoice_array['Invoice'] = array(
                                        'clinic_id' => $dt['redemption_details']['clinic_id'],
                                        'amount' => $amountpay,
                                        'transaction_fee' => $transactionFee,
                                        'invoice_id' => $trnsid,
                                        'mode' => 'Credit',
                                        'current_balance' => $curnbal,
                                        'payed_on' => $date2,
                                        'status' => 1
                                    );
                                    $this->Invoice->create();
                                    $this->Invoice->save($Invoice_array);
                                    $template_array = $this->Api->get_template(10);
                                    $link = str_replace('[staff_name]', $stname, $template_array['content']);
                                    $link1 = str_replace('[credit_amount]', $amountpay, $link);
                                    $link2 = str_replace('[current_balance]', $curnbal, $link1);
                                    $Email2 = new CakeEmail(MAILTYPE);

                                    $Email2->from(array(SUPER_ADMIN_EMAIL_STAFF => 'BuzzyDoc'));

                                    $Email2->to($stemail);
                                    $Email2->subject($template_array['subject'])
                                            ->template('buzzydocother')
                                            ->emailFormat('html');

                                    $Email2->viewVars(array('msg' => $link2
                                    ));
                                    $Email2->send();
                                } else {
                                    $clinicfautid.=$minimumdeposit['Clinic']['api_user'] . ',';

                                    $template_array = $this->Api->get_template(11);
                                    $link = str_replace('[staff_name]', $stname, $template_array['content']);
                                    $Email2 = new CakeEmail(MAILTYPE);

                                    $Email2->from(array(SUPER_ADMIN_EMAIL_STAFF => 'BuzzyDoc'));

                                    $Email2->to($stemail);
                                    $Email2->subject($template_array['subject'])
                                            ->template('buzzydocother')
                                            ->emailFormat('html');

                                    $Email2->viewVars(array('msg' => $link
                                    ));
                                    $Email2->send();
                                }
//                            }
                            }
                        }
                    }
                }
                $template_array1 = $this->Api->get_template(13);
                $linkorder = str_replace('[username]', $checkpoint['User']['first_name'], $template_array1['content']);
                $Email = new CakeEmail(MAILTYPE);

                $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));

                $Email->to($checkpoint['User']['email']);
                $Email->subject($template_array1['subject'])
                        ->template('buzzydocother')
                        ->emailFormat('html');

                $Email->viewVars(array('msg' => $linkorder,
                    'orderdetails' => $orderdetail
                ));
                $Email->send();
                echo 1;
            } else {
                echo 0;
            }
        }

        die;
    }

    /**
     * getting the rewards list for add to wish list.
     */
    public function getreward() {
        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');
        foreach ($sessionpatient['Reward'] as $reward) {
            if ($reward['Reward']['id'] == $this->request->data['reward_id']) {
                $reward_array = $reward;
            }
        }

        $WishLists = $this->WishList->find('all', array('conditions' => array('WishList.user_id' => $sessionpatient['customer_info']['user']['id'], 'WishList.clinic_id' => $sessionpatient['clinic_id'], 'WishList.reward_id' => $this->request->data['reward_id'])));

        if (empty($WishLists)) {
            $WishLists = 0;
        } else {
            $WishLists = 1;
        }

        $img = '<img src="' . $reward_array['Reward']['imagepath'] . '" alt="" width="175" height="117">';

        $reward_array['Reward']['imagepath'] = $img;
        $response = array('WishLists' => $WishLists, 'rewards' => $reward_array);
        echo json_encode($response);

        exit;
    }

    /**
     * getting the wishlist and rewards list.
     */
    public function rewarddetail() {
        $this->layout = "patientLayout";
        $sessionpatient = $this->Session->read('patient');
        foreach ($sessionpatient['Reward'] as $reward) {
            if ($reward['Reward']['id'] == $this->request->data['which_reward_id']) {
                $reward_array = $reward;
            }
        }
        $options['conditions'] = array('WishList.reward_id' => $this->request->data['which_reward_id'], 'WishList.clinic_id' => $sessionpatient['clinic_id'], 'WishList.user_id' => $sessionpatient['customer_info']['user']['id']);
        $WishLists = $this->WishList->find('first', $options);
        if (empty($WishLists)) {
            $WishLists = 0;
        } else {
            $WishLists = 1;
        }
        $img = '<img src="' . $reward_array['Reward']['imagepath'] . '" width="175" height="117">';

        $reward_array['Reward']['imagepath'] = $img;
        $response = array('WishLists' => $WishLists, 'rewards' => $reward_array);
        $this->set('rewards', $response);
    }

    /**
     * Add to wish list.
     */
    public function addwishlist() {
        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');
        $redeem_reward_array['WishList'] = array('reward_id' => $_POST['reward_id'], 'user_id' => $sessionpatient['customer_info']['user']['id'], 'clinic_id' => $sessionpatient['clinic_id']);
        $this->WishList->create();
        if ($this->WishList->save($redeem_reward_array)) {
            echo '1';
        } else {
            echo '0';
        }
        exit;
    }

    /**
     * Getting the contest list.
     */
    public function contest() {
        $this->layout = "patientLayout";
        $sessionpatient = $this->Session->read('patient');
        $contest = "SELECT * FROM contest_clinics as cc join contests as c on c.id=cc.contest_id WHERE cc.clinic_id=" . $sessionpatient['clinic_id'];
        $challenges = $this->ContestClinic->query($contest);
        $this->set('challenges', $challenges);
        $rdquery = "SELECT * FROM rewards WHERE clinic_id=" . $sessionpatient['clinic_id'] . " and points!='' and description !='' UNION SELECT rewards.* from rewards inner join clinic_rewards on clinic_rewards.reward_id=rewards.id WHERE clinic_rewards.clinic_id=" . $sessionpatient['clinic_id'] . " and rewards.points!='' and rewards.description !=''  order by points limit 6";
        $query = $this->Reward->query($rdquery);
        $this->set('Reward', $query);
    }

    /**
     * Getting the contest details.
     */
    public function contestdetail() {
        $sessionpatient = $this->Session->read('patient');
        $this->layout = "patientLayout";
        $contest = "SELECT * FROM contest_clinics as cc join contests as c on c.id=cc.contest_id WHERE cc.clinic_id=" . $sessionpatient['clinic_id'];
        $challenges = $this->ContestClinic->query($contest);
        $this->set('challenges', $challenges);
    }

    /**
     * Verify card number is exist with pratice.
     */
    public function verifycard() {
        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');
        $options['conditions'] = array('CardNumber.card_number' => $this->request->data['card_number'], 'CardNumber.clinic_id' => $sessionpatient['clinic_id']);
        $CardNumber = $this->CardNumber->find('first', $options);
        if (isset($sessionpatient['api_user'])) {
            if (!empty($CardNumber)) {

                if ($CardNumber['CardNumber']['status'] == 2) {
                    $Patients = $this->ClinicUser->find('first', array(
                        'joins' => array(
                            array(
                                'table' => 'users',
                                'alias' => 'user',
                                'type' => 'INNER',
                                'conditions' => array(
                                    'user.id = ClinicUser.user_id'
                                )
                            )),
                        'conditions' => array(
                            'ClinicUser.card_number' => $this->request->data['card_number'],
                            'user.email' => '',
                            'ClinicUser.clinic_id' => $sessionpatient['clinic_id'],
                            'user.blocked !=' => 1
                        ),
                        'fields' => array('ClinicUser.*', 'user.*')
                    ));
                    if (empty($Patients)) {
                        $response = array('status' => 1);

                        echo json_encode($response);
                    } else {

                        $response = array('id' => $Patients['user']['id'], 'first_name' => $Patients['user']['first_name'], 'last_name' => $Patients['user']['last_name'], 'card_number' => $Patients['ClinicUser']['card_number'], 'status' => 5);

                        echo json_encode($response);
                    }
                } else if ($CardNumber['CardNumber']['status'] == 1) {
                    $response = array('status' => 2);

                    echo json_encode($response);
                } else if ($CardNumber['CardNumber']['status'] == 0) {
                    $response = array('status' => 4);

                    echo json_encode($response);
                }
            } else {
                $response = array('status' => 0);

                echo json_encode($response);
            }
        } else {
            $response = array('status' => 3);

            echo json_encode($response);
        }

        exit;
    }

    /**
     * Send enquiry mail to staff user.
     */
    public function dofootenquirysubmit() {

        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');

        $Email = new CakeEmail(MAILTYPE);
        $Email->from(array($this->request->data['enquiry_email'] => 'BuzzyDoc'));
        $Email->to("help@buzzydoc.com");

        $Email->subject("Web Enquiry")
                ->template('inquiry')
                ->emailFormat('html');
        $Email->viewVars(array(
            'logo' => S3Path . $sessionpatient['Themes']['patient_logo_url'],
            'site_url' => 'http://' . $_SERVER['HTTP_HOST'],
            'redeem_data' => $this->request->data,
            'theme' => $sessionpatient['Themes']
        ));
        if ($Email->send()) {
            echo "Your message has been sent successfully. Our team will get in touch shortly.";
            exit;
        } else {
            echo "Oops, there seems to have been an error. Please try again later or write to use directly at help@buzzydoc.com";
            exit;
        }
    }

    /**
     * Facebook like point allocation.
     */
    public function facebookpointallocation() {

        $Patients1 = array();
        $data = array();
        $sessionpatient = $this->Session->read('patient');

        $Patients1 = $this->ClinicUser->find('first', array(
            'joins' => array(
                array(
                    'table' => 'users',
                    'alias' => 'user',
                    'type' => 'INNER',
                    'conditions' => array(
                        'user.id = ClinicUser.user_id'
                    )
                )),
            'conditions' => array(
                'ClinicUser.card_number' => $sessionpatient['customer_info']['ClinicUser']['card_number'],
                'ClinicUser.clinic_id' => $sessionpatient['clinic_id']
            ),
            'fields' => array('ClinicUser.*', 'user.*')
        ));

        $patients_id = $Patients1['user']['id'];


        $config = array(
            'appId' => $sessionpatient['Themes']['fb_app_id'],
            'secret' => $sessionpatient['Themes']['fb_app_key'],
            'allowSignedRequest' => false
        );

        $facebook = new Facebook($config);
        $user = $facebook->getUser();

        if ($user) {
            $user_fb_email = '';
            $user_profile = $facebook->api('/me');
            if (array_key_exists("email", $user_profile)) {
                $user_fb_email = $user_profile['email'];
            }

            if (($Patients1['ClinicUser']['facebook_like_status'] == 0) || ($Patients1['ClinicUser']['facebook_like_status'] == '') && ($Patients1['ClinicUser']['facebook_like_status'] != 1)) {

                $options_pro['fields'] = array('Promotion.id', 'Promotion.value', 'Promotion.description', 'Promotion.operand');
                $options_pro['conditions'] = array('Promotion.clinic_id' => $sessionpatient['clinic_id'], 'Promotion.description like' => '%Facebook Like%');

                $Promotions = $this->Promotion->find('first', $options_pro);

                $data['user_id'] = $sessionpatient['customer_info']['user']['id'];
                $data['card_number'] = $sessionpatient['customer_info']['ClinicUser']['card_number'];
                $data['first_name'] = $sessionpatient['customer_info']['user']['first_name'];
                $data['last_name'] = $sessionpatient['customer_info']['user']['last_name'];
                $data['activity_type'] = 'N';
                if (!empty($Promotions)) {
                    $data['promotion_id'] = $Promotions['Promotion']['id'];
                    $data['amount'] = $Promotions['Promotion']['value'];
                } else {
                    $data['amount'] = 100;
                }
                $data['activity_type'] = 'N';
                $data['authorization'] = 'facebook point allocation';
                $data['clinic_id'] = $sessionpatient['clinic_id'];
                $data['date'] = date('Y-m-d H:i:s');
                $data['status'] = 'New';
                $data['is_buzzydoc'] = $sessionpatient['is_buzzydoc'];
                $this->Transaction->create();

                if ($this->Transaction->save($data)) {

                    $getfirstTransaction = $this->Api->get_firsttransaction($sessionpatient['customer_info']['user']['id'], $sessionpatient['clinic_id']);
                    if ($getfirstTransaction == 1 && $sessionpatient['customer_info']['user']['email'] != '' && $data['amount']>0) {
                        $template_array = $this->Api->get_template(39);
                        $link1 = str_replace('[username]', $sessionpatient['customer_info']['user']['first_name'], $template_array['content']);
                        $link = str_replace('[points]', $data['amount'], $link1);
                        $link2 = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $link);
                        $Email = new CakeEmail(MAILTYPE);

                        $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));

                        $Email->to($sessionpatient['customer_info']['user']['email']);
                        $Email->subject($template_array['subject'])
                                ->template('buzzydocother')
                                ->emailFormat('html');
                        $Email->viewVars(array('msg' => $link2
                        ));
                        $Email->send();
                    }

                    $options2['conditions'] = array('Notification.user_id' => $sessionpatient['customer_info']['user']['id'], 'Notification.clinic_id' => $sessionpatient['clinic_id'], 'Notification.earn_points' => 1);
                    $Notifications = $this->Notification->find('first', $options2);
                    if (!empty($Notifications) && $sessionpatient['customer_info']['user']['email'] != '' && $data['amount']>0) {
                        $rewardlogin = rtrim($sessionpatient['Themes']['patient_url'], '/') . "/rewards/login/" . base64_encode('redeem') . "/" . base64_encode($sessionpatient['customer_info']['ClinicUser']['card_number']) . "/" . base64_encode($sessionpatient['customer_info']['user']['id']) . "/Unsubscribe";


                        $template_array = $this->Api->get_template(1);
                        $link = str_replace('[username]', $sessionpatient['customer_info']['user']['first_name'], $template_array['content']);
                        $link1 = str_replace('[click_here]', '<a href="' . rtrim($sessionpatient['Themes']['patient_url'], '/') . '">Click Here</a>', $link);
                        $link2 = str_replace('[clinic_name]', $sessionpatient['Themes']['api_user'], $link1);
                        $link3 = str_replace('[points]', $data['amount'], $link2);
                        $Email = new CakeEmail(MAILTYPE);

                        $Email->from(array(SUPER_ADMIN_EMAIL => 'BuzzyDoc'));

                        $Email->to($sessionpatient['customer_info']['user']['email']);
                        $Email->subject($template_array['subject'])
                                ->template('buzzydocother')
                                ->emailFormat('html');
                        $Email->viewVars(array('msg' => $link3
                        ));
                        $Email->send();
                    }


                    $this->ClinicUser->query("update clinic_users set facebook_like_status=1,facebook_email='" . $user_fb_email . "' where user_id=" . $sessionpatient['customer_info']['user']['id'] . " and card_number=" . $sessionpatient['customer_info']['ClinicUser']['card_number']);
                    if ($sessionpatient['is_buzzydoc'] == 1) {
                        $options['conditions'] = array('User.id' => $sessionpatient['customer_info']['user']['id']);

                        $userpoint = $this->User->find('first', $options);
                        $totalpoint = $userpoint['User']['points'] + $data['amount'];
                        $this->User->query("UPDATE `users` SET `points` = '" . $totalpoint . "' WHERE `id` =" . $sessionpatient['customer_info']['user']['id']);
                        $this->Session->write('patient.customer_info.user.points', $totalpoint);
                    } else {
                        $options['conditions'] = array('ClinicUser.user_id' => $sessionpatient['customer_info']['user']['id'], 'ClinicUser.clinic_id' => $sessionpatient['clinic_id']);

                        $userpoint = $this->ClinicUser->find('first', $options);
                        $totalpoint = $userpoint['ClinicUser']['local_points'] + $data['amount'];
                        $this->User->query("UPDATE `clinic_users` SET `local_points` = '" . $totalpoint . "' WHERE `user_id` =" . $sessionpatient['customer_info']['user']['id'] . ' and clinic_id=' . $sessionpatient['clinic_id']);
                        $this->Session->write('patient.customer_info.ClinicUser.local_points', $totalpoint);
                    }
                    $this->Session->write('patient.customer_info.ClinicUser.facebook_like_status', 1);

                    $this->set('errorMsg', "We've credited 100 points to you as we found that you've already liked our Facebook page. Thanks!");
                    echo '1';
                    exit;
                }
            }
        } else {
            echo '2';
            exit;
        }
    }

    /**
     * @depricated
     * @return type
     */
    public function stafflogin() {

        $this->layout = "patientSelfCheckinLogin";
        $sessionpatient = $this->Session->read('patient');
        if ($this->request->is('post')) {

            if (isset($sessionpatient['clinic_id'])) {

                $staff = $this->Staff->find('first', array('conditions' => array('Staff.staff_id' => $this->request->data['login']['staff_name'], 'Staff.staff_password' => md5($this->request->data['login']['staff_password']))));
                if (!empty($staff)) {
                    $this->Session->write('patient.selfcheckin.var.staff_name', $staff['Staff']['staff_id']);
                    $this->Session->write('patient.selfcheckin.var.staff_password', $staff['Staff']['staff_password']);
                    return $this->redirect(array('controller' => 'rewards', 'action' => 'selfcheckin'));
                } else {
                    $this->Session->setFlash(__('Invalid Credentials'));
                }
            } else {
                $this->Session->setFlash(__('Clinic does not exists.'));
            }
        }
    }

    /**
     * @depricated
     * @return type
     */
    public function selfcheckin() {
        $this->layout = "patientSelfCheckin";
        $sessionpatient = $this->Session->read('patient');
        if (isset($sessionpatient['selfcheckin']['var']['patient_name']) && $sessionpatient['selfcheckin']['var']['patient_name'] != '') {

            return $this->redirect('/rewards/selfcheckinportal/');
        }
    }

    /**
     * Getting the persent profile completion for patient account.
     * @return type
     */
    public function checkprofilecompletion() {

        $this->layout = "patientSelfCheckin";
        $sessionpatient = $this->Session->read('patient');

        if (empty($sessionpatient['selfcheckin']['var']['staff_name']) && $this->params['action'] != 'stafflogin') {

            return $this->redirect('/rewards/stafflogin/');
        } else if (empty($sessionpatient['selfcheckin']['var']['patient_name']) && $this->params['action'] != 'stafflogin' && $this->params['action'] != 'selfcheckin') {

            return $this->redirect('/rewards/selfcheckin/');
        }
        if ($sessionpatient['selfcheckin']['var']['last_log'] != '0000-00-00 00:00:00') {
            $ts1 = strtotime($sessionpatient['selfcheckin']['var']['last_log']);
            $ts2 = strtotime(date('Y-m-d H:i:s'));
            $seconds_diff = $ts2 - $ts1;
            $log_diff = floor($seconds_diff / 3600 / 24);
        }
        $this->loadModel('user');
        $Patients = $this->user->find('first', array('conditions' => array('user.id' => $sessionpatient['selfcheckin']['var']['patient_id'])));

        $total = count($sessionpatient['ProfileField']) + 3;
        $m = 0;

        foreach ($sessionpatient['customer_info']['ProfileField'] as $field_sorted) {
            if (isset($field_sorted['ProfileFieldUser']['value']) && $field_sorted['profile_field'] != 'street2') {

                if (isset($field_sorted['ProfileFieldUser']['value']) && $field_sorted['ProfileFieldUser']['value'] != '') {
                    $m++;
                }
            }
        }
        if ($sessionpatient['customer_info']['user']['email'] != '') {
            $m++;
        }
        if ($sessionpatient['customer_info']['user']['custom_date'] != '') {
            $m++;
        }
        if ($sessionpatient['customer_info']['user']['first_name'] != '') {
            $m++;
        }
        if ($sessionpatient['customer_info']['user']['last_name'] != '') {
            $m++;
        }

        $completed = $m;
        $uncompleted = $total - $m;
        $complitionper = number_format(($m / $total) * 100);

        if ($complitionper < 100) {
            
        } else if (isset($log_diff) && $log_diff < 31) {
            $this->set('log', $log_diff);
        } else {
            return $this->redirect(array('controller' => 'rewards', 'action' => 'selfcheckinportal'));
        }
    }

    /**
     * @depricated
     * @return type
     */
    public function selfcheckinportal() {
        $this->layout = "patientSelfCheckin";
        $sessionpatient = $this->Session->read('patient');
        if (empty($sessionpatient['selfcheckin']['var']['staff_name']) && $this->params['action'] != 'stafflogin') {

            return $this->redirect('/rewards/stafflogin/');
        } else if (empty($sessionpatient['selfcheckin']['var']['patient_name']) && $this->params['action'] != 'stafflogin' && $this->params['action'] != 'selfcheckin') {

            return $this->redirect('/rewards/selfcheckin/');
        }
        $sessionpatient1 = $this->Session->read('patient');
        $question = $sessionpatient1['selfcheckin']['question_list'];

        $totalques = count($question);
        $selquestion = array();
        foreach ($question as $ques) {
            if (isset($sessionpatient['selfcheckin']['current_question'])) {
                
            } else {
                $sessionpatient['selfcheckin']['current_question'] = 1;
            }
            if ($ques['id'] == $sessionpatient['selfcheckin']['current_question']) {
                $selquestion = $ques;
            }
        }
        $this->set('TotalQuestion', $totalques);
        $this->set('Question', $selquestion);
    }

    /**
     * @depricated
     * @return type
     */
    public function selfcheckinportalques() {
        $this->layout = "";

        $sessionpatient = $this->Session->read('patient');
        $question = $sessionpatient['selfcheckin']['question_list'];
        $totalques = count($question);

        $qid = $_POST['question_id'] + 1;
        if ($qid == $totalques) {
            $this->User->query("UPDATE `users` SET `selfcheckin_log` = '" . date('Y-m-d H:i:s') . "' WHERE `id` =" . $sessionpatient['selfcheckin']['var']['patient_id']);
            $buttype = "Finish";
        } else {
            $buttype = "Next";
        }
        if (isset($_POST['question'])) {
            if (is_array($_POST['question'])) {
                $answer = implode(',', $_POST['question']);
            } else {
                $answer = $_POST['question'];
            }
        } else {
            $answer = '';
        }
        foreach ($question as $ques) {
            if ($ques['id'] == $_POST['question_id'] && $ques['answer'] == $answer) {
                $options['conditions'] = array('User.id' => $sessionpatient['selfcheckin']['var']['patient_id']);
                $userpoint = $this->User->find('first', $options);
                $data['user_id'] = $userpoint['User']['id'];
                $data['card_number'] = $sessionpatient['selfcheckin']['var']['patient_name'];
                $data['first_name'] = $userpoint['User']['first_name'];
                $data['last_name'] = $userpoint['User']['last_name'];
                $data['amount'] = 50;
                $data['activity_type'] = 'N';
                $data['authorization'] = 'self checkin point allocation';
                $data['clinic_id'] = $sessionpatient['clinic_id'];
                $data['date'] = date('Y-m-d H:i:s');
                $data['status'] = 'New';
                $data['is_buzzydoc'] = $sessionpatient['is_buzzydoc'];
                $this->Transaction->create();
                $this->Transaction->save($data);
                if ($sessionpatient['is_buzzydoc'] == 1) {
                    $totalpoint = $userpoint['User']['points'] + $data['amount'];
                    $this->User->query("UPDATE `users` SET `points` = '" . $totalpoint . "' WHERE `id` =" . $userpoint['User']['id']);
                } else {
                    $options['conditions'] = array('ClinicUser.user_id' => $sessionpatient['selfcheckin']['var']['patient_id'], 'ClinicUser.clinic_id' => $sessionpatient['clinic_id']);
                    $userpoint = $this->ClinicUser->find('first', $options);
                    $totalpoint = $userpoint['ClinicUser']['local_points'] + $data['amount'];
                    $this->User->query("UPDATE `clinic_users` SET `local_points` = '" . $totalpoint . "' WHERE `user_id` =" . $userpoint['User']['id'] . ' and clinic_id=' . $sessionpatient['clinic_id']);
                }
            }
        }

        $this->Session->write('patient.selfcheckin.current_question', $qid);

        if ($qid <= $totalques) {


            $selquestion1 = array();
            foreach ($question as $ques) {
                if ($ques['id'] == $qid) {
                    $selquestion1 = $ques;
                }
            }

            $response = array('id' => $selquestion1['id'], 'quescnt' => $selquestion1['quescnt'], 'question' => $selquestion1['question'], 'submitbutton' => $buttype);
        } else {
            $response = array('id' => 'finish');
        }
        echo json_encode($response);
        die;
    }

    /**
     * @depricated
     * @return type
     */
    public function stafflogout() {

        $this->Session->delete('patient.selfcheckin');
        return $this->redirect(array('controller' => 'rewards', 'action' => 'stafflogin'));
    }

    /**
     * Logout from rewards site.
     * @return type
     */
    public function patientlogout() {
        $sessionpatient = $this->Session->read('patient');
        $this->User->query("UPDATE `users` SET `selfcheckin_log` = '" . date('Y-m-d H:i:s') . "' WHERE `id` =" . $sessionpatient['selfcheckin']['var']['patient_id']);
        $this->Session->delete('patient.selfcheckin.var.patient_name');
        $this->Session->delete('patient.selfcheckin.var.patient_password');
        $this->Session->delete('patient.selfcheckin.var.last_log');
        $this->Session->delete('patient.selfcheckin.current_question');
        $this->Session->delete('patient.Reward');
        $this->Session->delete('patient.var.patient_name');
        $this->Session->delete('patient.var.patient_password');
        $this->Session->delete('patient.customer_info');
        $this->Session->delete('patient.customer_info.ClinicUser');

        return $this->redirect(array('controller' => 'rewards', 'action' => 'selfcheckin'));
    }

    /**
     * Get next free card number for auto pull when try to signup by patient.
     */
    public function getNextFreeCard() {
        $this->layout = "";
        $getfreecard = $this->Api->get_freecardDetails($_POST['clinic_id']);
        echo $getfreecard['card_number'];
        exit;
    }

    public function checkCardNumber() {
        $this->layout = "";
        $sessionpatient = $this->Session->read('patient');
        if (isset($_POST['card_number'])) {
            $users_card_number = $this->CardNumber->find('first', array(
                'conditions' => array(
                    'CardNumber.clinic_id' => $sessionpatient['clinic_id'],
                    'CardNumber.card_number' => $_POST['card_number']
                )
            ));
            if (empty($users_card_number)) {
                echo 1;
            } else if ($users_card_number['CardNumber']['status'] == 1) {
                echo 0;
            } else {
                echo 2;
            }
        }

        exit;
    }

}

?>
