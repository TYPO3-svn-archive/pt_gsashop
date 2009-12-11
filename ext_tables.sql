#
# Table structure for table 'tx_ptgsashop_orders'
#
CREATE TABLE tx_ptgsashop_orders (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    order_timestamp int(11) DEFAULT '0' NOT NULL,
    is_net tinyint(3) DEFAULT '0' NOT NULL,
    is_taxfree tinyint(3) DEFAULT '0' NOT NULL,
    is_tc_acc tinyint(3) DEFAULT '0' NOT NULL,
    is_wd_acc tinyint(3) DEFAULT '0' NOT NULL,
    is_mult_del tinyint(3) DEFAULT '0' NOT NULL,
    applSpecData longtext NOT NULL,
    applSpecDataClass varchar(255) DEFAULT '' NOT NULL,
	
	irreBillingAddress int(11) DEFAULT '0' NOT NULL,
	irrePaymentMethod int(11) DEFAULT '0' NOT NULL,
	irreDeliveries int(11) DEFAULT '0' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_ptgsashop_orders_deliveries'
#
CREATE TABLE tx_ptgsashop_orders_deliveries (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    orders_id int(11) DEFAULT '0' NOT NULL,
    is_orderbase_net tinyint(3) DEFAULT '0' NOT NULL,
    is_orderbase_taxfree tinyint(3) DEFAULT '0' NOT NULL,
    is_physical tinyint(3) DEFAULT '0' NOT NULL,

	irreShippingAddress int(11) DEFAULT '0' NOT NULL,
	irreDispatchCost int(11) DEFAULT '0' NOT NULL,
	irreArticles int(11) DEFAULT '0' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_ptgsashop_orders_addresses'
#
CREATE TABLE tx_ptgsashop_orders_addresses (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    orders_id int(11) DEFAULT '0' NOT NULL,
    deliveries_id int(11) DEFAULT '0' NOT NULL,
    post1 varchar(255) DEFAULT '' NOT NULL,
    post2 varchar(255) DEFAULT '' NOT NULL,
    post3 varchar(255) DEFAULT '' NOT NULL,
    post4 varchar(255) DEFAULT '' NOT NULL,
    post5 varchar(255) DEFAULT '' NOT NULL,
    post6 varchar(255) DEFAULT '' NOT NULL,
    post7 varchar(255) DEFAULT '' NOT NULL,
    country char(2) DEFAULT '' NOT NULL,
    gsa_id_adresse int(11) DEFAULT '0' NOT NULL,
    gsa_id_ansch int(11) DEFAULT '0' NOT NULL,
    t3_id_ansch int(11) DEFAULT '0' NOT NULL,

    irreParentTable varchar(255) DEFAULT '' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_ptgsashop_orders_articles'
#
CREATE TABLE tx_ptgsashop_orders_articles (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    orders_id int(11) DEFAULT '0' NOT NULL,
    deliveries_id int(11) DEFAULT '0' NOT NULL,
    gsa_id_artikel int(11) DEFAULT '0' NOT NULL,
    quantity int(11) DEFAULT '0' NOT NULL,
    art_no varchar(255) DEFAULT '' NOT NULL,
    description varchar(255) DEFAULT '' NOT NULL,
    price_calc_qty int(11) DEFAULT '0' NOT NULL,
    price_category int(11) DEFAULT '0' NOT NULL,
    date_string varchar(10) DEFAULT '' NOT NULL,
    tax_code char(2) DEFAULT '' NOT NULL,
    tax_percentage double(11,2) DEFAULT '0.00' NOT NULL,
    fixedCost1 double(13,4) DEFAULT '0.0000' NOT NULL,
    fixedCost2 double(13,4) DEFAULT '0.0000' NOT NULL,
    price_net double(15,6) DEFAULT '0.000000' NOT NULL,
    price_gross double(13,4) DEFAULT '0.0000' NOT NULL,
    userField01 varchar(255) DEFAULT '' NOT NULL,
    userField02 varchar(255) DEFAULT '' NOT NULL,
    userField03 varchar(255) DEFAULT '' NOT NULL,
    userField04 varchar(255) DEFAULT '' NOT NULL,
    userField05 varchar(255) DEFAULT '' NOT NULL,
    userField06 varchar(255) DEFAULT '' NOT NULL,
    userField07 varchar(255) DEFAULT '' NOT NULL,
    userField08 varchar(255) DEFAULT '' NOT NULL,
    applSpecData longtext NOT NULL,
    applSpecDataClass varchar(255) DEFAULT '' NOT NULL,
    artrelApplSpecUid int(11) DEFAULT '0' NOT NULL,
    artrelApplIdentifier varchar(255) DEFAULT '' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_ptgsashop_orders_dispatchcost'
#
CREATE TABLE tx_ptgsashop_orders_dispatchcost (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    orders_id int(11) DEFAULT '0' NOT NULL,
    deliveries_id int(11) DEFAULT '0' NOT NULL,
    cost_type_name varchar(255) DEFAULT '' NOT NULL,
    cost_comp_1 double(13,4) DEFAULT '0.0000' NOT NULL,
    cost_comp_2 double(13,4) DEFAULT '0.0000' NOT NULL,
    cost_comp_3 double(13,4) DEFAULT '0.0000' NOT NULL,
    cost_comp_4 double(13,4) DEFAULT '0.0000' NOT NULL,
    allowance_comp_1 double(13,4) DEFAULT '0.0000' NOT NULL,
    allowance_comp_2 double(13,4) DEFAULT '0.0000' NOT NULL,
    allowance_comp_3 double(13,4) DEFAULT '0.0000' NOT NULL,
    allowance_comp_4 double(13,4) DEFAULT '0.0000' NOT NULL,
    cost_tax_code char(2) DEFAULT '' NOT NULL,
    tax_percentage double(11,2) DEFAULT '0.00' NOT NULL,
    dispatch_cost double(13,4) DEFAULT '0.0000' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_ptgsashop_orders_paymentmethods'
#
CREATE TABLE tx_ptgsashop_orders_paymentmethods (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    orders_id int(11) DEFAULT '0' NOT NULL,
    method char(3) DEFAULT '' NOT NULL,
    epayment_success tinyint(3) DEFAULT '0' NOT NULL,
    epayment_trans_id varchar(255) DEFAULT '' NOT NULL,
    epayment_ref_id varchar(255) DEFAULT '' NOT NULL,
    epayment_short_id varchar(255) DEFAULT '' NOT NULL,
    bank_account_holder varchar(27) DEFAULT '' NOT NULL,
    bank_name varchar(40) DEFAULT '' NOT NULL,
    bank_account_number varchar(30) DEFAULT '' NOT NULL,
    bank_code varchar(10) DEFAULT '' NOT NULL,
    bank_bic varchar(11) DEFAULT '' NOT NULL,
    bank_iban varchar(34) DEFAULT '' NOT NULL,
    gsa_dta_acc_no int(11) DEFAULT '0' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_ptgsashop_order_wrappers'
#
CREATE TABLE tx_ptgsashop_order_wrappers (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    customer_id int(11) DEFAULT '0' NOT NULL,
    related_doc_no varchar(255) DEFAULT '' NOT NULL,
    orders_id int(11) DEFAULT '0' NOT NULL,
    order_timestamp int(11) DEFAULT '0' NOT NULL,
    sum_net varchar(12) DEFAULT '' NOT NULL,
    sum_gross varchar(12) DEFAULT '' NOT NULL,    
    wf_status_code tinyint(2) DEFAULT '0' NOT NULL,
    wf_lastuser_id int(11) DEFAULT '0' NOT NULL,
    wf_lock_userid int(11) DEFAULT '0' NOT NULL,
    wf_lock_timestamp int(11) DEFAULT '0' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);



#
# Table structure for table 'tx_ptgsashop_workflow'
#
CREATE TABLE tx_ptgsashop_workflow (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    status_code tinyint(2) DEFAULT '0' NOT NULL,
    name varchar(255) DEFAULT '' NOT NULL,
    description varchar(255) DEFAULT '' NOT NULL,
    auth_groups_view blob NOT NULL,
    auth_groups_use blob NOT NULL,
    update_order tinyint(1) DEFAULT '0' NOT NULL,
    condition_method tinyint(1) DEFAULT '0' NOT NULL,
    permission_method tinyint(1) DEFAULT '0' NOT NULL,
    approve_action_method tinyint(1) DEFAULT '0' NOT NULL,
    deny_action_method tinyint(1) DEFAULT '0' NOT NULL,
    advance_action_method tinyint(1) DEFAULT '0' NOT NULL,
    halt_action_method tinyint(1) DEFAULT '0' NOT NULL,
    approve_status_code char(2) DEFAULT '' NOT NULL,
    deny_status_code char(2) DEFAULT '' NOT NULL,
    advance_status_code char(2) DEFAULT '' NOT NULL,
    label_choice varchar(255) DEFAULT '' NOT NULL,
    label_approve varchar(255) DEFAULT '' NOT NULL,
    label_deny varchar(255) DEFAULT '' NOT NULL,
    label_confirm_approve varchar(255) DEFAULT '' NOT NULL,
    label_confirm_deny varchar(255) DEFAULT '' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);


#
# Table structure for table 'tx_ptgsashop_amendmentlog'
#
CREATE TABLE tx_ptgsashop_amendmentlog (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    order_wrapper_id int(11) DEFAULT '0' NOT NULL,
    log_entry blob NOT NULL,
    status_prev tinyint(2) DEFAULT '0' NOT NULL,
    status_new tinyint(2) DEFAULT '0' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid)
);


#
# Table structure for table 'tx_ptgsashop_artrel'
#
CREATE TABLE tx_ptgsashop_artrel (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    gsa_art_nummer int(11) DEFAULT '0' NOT NULL,
    max_amount int(11) DEFAULT '0' NOT NULL,
    exclusion_articles varchar(255) DEFAULT '' NOT NULL,
    required_articles varchar(255) DEFAULT '' NOT NULL,
    related_articles varchar(255) DEFAULT '' NOT NULL,
    bundled_articles varchar(255) DEFAULT '' NOT NULL,
    appl_spec_uid int(11) DEFAULT '0' NOT NULL,
    appl_identifier varchar(255) DEFAULT '' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid), 
    KEY gsa_art_nr (gsa_art_nummer)
);


#
# Table structure for table 'tx_ptgsashop_article_images'
#
CREATE TABLE tx_ptgsashop_article_images (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
    gsa_art_nummer int(11) DEFAULT '0' NOT NULL,
    path tinytext NOT NULL,
    description longtext NOT NULL,
    alt tinytext NOT NULL,
    title tinytext NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid), 
    KEY gsa_art_nr (gsa_art_nummer)
);


#
# Table structure for table 'tx_ptgsashop_cache_articles'
#
CREATE TABLE tx_ptgsashop_cache_articles (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    gsadb_artnr varchar(120) DEFAULT '' NOT NULL,
    gsadb_match varchar(255) DEFAULT '' NOT NULL,
    gsadb_match2 varchar(60) DEFAULT '' NOT NULL,
    gsadb_passiv tinyint(4) DEFAULT '0' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid), 
    KEY gsadb_artnr (gsadb_artnr),
    KEY gsadb_match (gsadb_match),
    KEY gsadb_match2 (gsadb_match2)
);