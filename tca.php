<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

// TODO (Fabrizio): correct order of TCA fields

$TCA['tx_ptgsashop_orders'] = array(
    'ctrl' => $TCA['tx_ptgsashop_orders']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_cruser_id,order_timestamp,is_net,is_taxfree,is_tc_acc,is_wd_acc,is_mult_del,applSpecData,applSpecDataClass'
    ),
    'feInterface' => $TCA['tx_ptgsashop_orders']['feInterface'],
    'columns' => array(
        'uid' => array(
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.uid',
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_cruser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.fe_cruser_id',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'eval' => 'required,int,nospace',
            )
        ),
        'order_timestamp' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.order_timestamp',
            'config' => array(
                'type' => 'input',
                'size' => '12',
                'max' => '20',
                'eval' => 'datetime, required',
                'checkbox' => '0',
                'default' => '0',
            )
        ),
        'is_net' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.is_net',
            'config' => array(
                'type' => 'check',
            )
        ),
        'is_taxfree' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.is_taxfree',
            'config' => array(
                'type' => 'check',
            )
        ),
        'is_tc_acc' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.is_tc_acc',
            'config' => array(
                'type' => 'check',
            )
        ),
        'is_wd_acc' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.is_wd_acc',
            'config' => array(
                'type' => 'check',
            )
        ),
        'is_mult_del' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.is_mult_del',
            'config' => array(
                'type' => 'check',
            )
        ),
        'applSpecData' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.applSpecData',
            'config' => array(
                'type' => 'text',
                'cols' => '48',
                'rows' => '10',
                #"pass_content" => 1,
            )
        ),
        'applSpecDataClass' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.applSpecDataClass',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'irreBillingAddress' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.irreBillingAddress',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_ptgsashop_orders_addresses',
                'foreign_table_field' => 'irreParentTable',
                'maxitems' => 1,
                'appearance' => array(
                    'newRecordLinkPosition' => 'none',
                    'collapseAll' => false,
                ),
                'foreign_field' => 'orders_id',
            )
        ),
        'irrePaymentMethod' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.irrePaymentMethod',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_ptgsashop_orders_paymentmethods',
                'maxitems' => 1,
                'appearance' => array(
                    'newRecordLinkPosition' => 'none',
                    'collapseAll' => false,
                ),
                'foreign_field' => 'orders_id',
            )
        ),
        'irreDeliveries' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.irreDeliveries',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_ptgsashop_orders_deliveries',
                'appearance' => array(
                    'newRecordLinkPosition' => 'none',
                    'collapseAll' => true,
                    'expandSingle' => true,
                ),
                'foreign_field' => 'orders_id',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'fe_cruser_id, order_timestamp, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.flags;1, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.irreBillingAddress, irreBillingAddress, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.irrePaymentMethod, irrePaymentMethod, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.irreDeliveries, irreDeliveries, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.applicationData, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.applicationData;2, --palette--;;3')
    ),
    'palettes' => array(
        '1' => array(
            'showitem' => 'is_net, is_taxfree, is_tc_acc, is_wd_acc, is_mult_del',
            'canNotCollapse' => true
        ),
        '2' => array(
            'showitem' => 'applSpecDataClass',
            'canNotCollapse' => true
        ),
        '3' => array(
            'showitem' => 'applSpecData',
            'canNotCollapse' => true
        ),
    )
);



$TCA['tx_ptgsashop_orders_deliveries'] = array(
    'ctrl' => $TCA['tx_ptgsashop_orders_deliveries']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_cruser_id,orders_id,is_orderbase_net,is_orderbase_taxfree,is_physical'
    ),
    'feInterface' => $TCA['tx_ptgsashop_orders_deliveries']['feInterface'],
    'columns' => array(
        'uid' => array(
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.uid',
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_cruser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.fe_cruser_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'orders_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.orders_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'is_orderbase_net' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.is_orderbase_net',
            'config' => array(
                'type' => 'check',
            )
        ),
        'is_orderbase_taxfree' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.is_orderbase_taxfree',
            'config' => array(
                'type' => 'check',
            )
        ),
        'is_physical' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.is_physical',
            'config' => array(
                'type' => 'check',
            )
        ),
        'irreShippingAddress' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.irreShippingAddress',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_ptgsashop_orders_addresses',
                'foreign_table_field' => 'irreParentTable',
                'maxitems' => 1,
                'appearance' => array(
                    'newRecordLinkPosition' => 'none',
                    'collapseAll' => false,
                ),
                'foreign_field' => 'deliveries_id',
            )
        ),
        'irreDispatchCost' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.irreDispatchCost',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_ptgsashop_orders_dispatchcost',
                'maxitems' => 1,
                'appearance' => array(
                    'newRecordLinkPosition' => 'none',
                    'collapseAll' => false,
                ),
                'foreign_field' => 'deliveries_id',
            )
        ),
        'irreArticles' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.irreArticles',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_ptgsashop_orders_articles',
                'appearance' => array(
                    'newRecordLinkPosition' => 'none',
                    'collapseAll' => true,
                    'expandSingle' => true,
                ),
                'foreign_field' => 'deliveries_id',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => '--palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders.flags;1, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.irreShippingAddress, irreShippingAddress, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.irreDispatchCost, irreDispatchCost, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_deliveries.irreArticles, irreArticles')
    ),
    'palettes' => array(
        '1' => array(
            'showitem' => 'is_orderbase_net, is_orderbase_taxfree, is_physical',
            'canNotCollapse' => true,
        )
    )
);



$TCA['tx_ptgsashop_orders_addresses'] = array(
    'ctrl' => $TCA['tx_ptgsashop_orders_addresses']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_cruser_id,orders_id,deliveries_id,post1,post2,post3,post4,post5,post6,post7,country,gsa_id_adresse,gsa_id_ansch'
    ),
    'feInterface' => $TCA['tx_ptgsashop_orders_addresses']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_cruser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.fe_cruser_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'orders_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.orders_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'deliveries_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.deliveries_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'post1' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.post1',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'post2' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.post2',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'post3' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.post3',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'post4' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.post4',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'post5' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.post5',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'post6' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.post6',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'post7' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.post7',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'country' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.country',
            'config' => array(
                'type' => 'input',
                'size' => '2',
                'eval' => 'trim',
            )
        ),
        'gsa_id_adresse' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.gsa_id_adresse',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'gsa_id_ansch' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.gsa_id_ansch',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'irreParentTable' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.irreParentTable',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '30',
                'eval' => 'trim',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => '--palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.postFields;1, --palette--;;2,  --palette--;;3, --palette--;;4, --palette--;;5, --palette--;;6, --palette--;;7, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_addresses.gsaReference;8')
    ),
    'palettes' => array(
        '1' => array(
            'showitem' => 'post1',
            'canNotCollapse' => true,
        ),
        '2' => array(
            'showitem' => 'post2',
            'canNotCollapse' => true,
        ),
        '3' => array(
            'showitem' => 'post3',
            'canNotCollapse' => true,
        ),
        '4' => array(
            'showitem' => 'post4',
            'canNotCollapse' => true,
        ),
        '5' => array(
            'showitem' => 'post5',
            'canNotCollapse' => true,
        ),
        '6' => array(
            'showitem' => 'post6',
            'canNotCollapse' => true,
        ),
        '7' => array(
            'showitem' => 'post7',
            'canNotCollapse' => true,
        ),
        '8' => array(
            'showitem' => 'gsa_id_adresse, gsa_id_ansch',
            'canNotCollapse' => true,
        ),
    )
);



$TCA['tx_ptgsashop_orders_articles'] = array(
    'ctrl' => $TCA['tx_ptgsashop_orders_articles']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_cruser_id,orders_id,deliveries_id,gsa_id_artikel,quantity,art_no,description,price_calc_qty,price_category,date_string,tax_code,tax_percentage,fixedCost1,fixedCost2,price_net,price_gross,userField01,userField02,userField03,userField04,userField05,userField06,userField07,userField08,applSpecData,applSpecDataClass,artrelApplSpecUid,artrelApplIdentifier'
    ),
    'feInterface' => $TCA['tx_ptgsashop_orders_articles']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_cruser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.fe_cruser_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'orders_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.orders_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'deliveries_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.deliveries_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'gsa_id_artikel' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.gsa_id_artikel',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'quantity' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.quantity',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'int,nospace',
            )
        ),
        'art_no' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.art_no',
            'config' => array(
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
            )
        ),
        'description' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.description',
            'config' => array(
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
            )
        ),
        'price_calc_qty' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.price_calc_qty',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'int,nospace',
            )
        ),
        'price_category' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.price_category',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'eval' => 'int,nospace',
            )
        ),
        'date_string' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.date_string',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,nospace',
            )
        ),
        'tax_code' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.tax_code',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '2',
                'eval' => 'trim,nospace',
            )
        ),
        'tax_percentage' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.tax_percentage',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'double2',
            )
        ),
        'fixedCost1' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.fixedCost1',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'fixedCost2' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.fixedCost2',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'price_net' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.price_net',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'price_gross' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.price_gross',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'userField01' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userField01',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'userField02' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userField02',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'userField03' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userField03',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'userField04' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userField04',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'userField05' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userField05',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'userField06' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userField06',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'userField07' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userField07',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'userField08' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userField08',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'applSpecData' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.applSpecData',
            'config' => array(
                'type' => 'text',
                'cols' => '48',
                'rows' => '10',
                #"pass_content" => 1,
            )
        ),
        'applSpecDataClass' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.applSpecDataClass',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'artrelApplSpecUid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.artrelApplSpecUid',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'int,nospace',
            )
        ),
        'artrelApplIdentifier' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.artrelApplIdentifier',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'gsa_id_artikel, --palette--;;1, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.quantities;2, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.prices;4, --palette--;;17, --palette--;;3, date_string, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.tax;16, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userFields, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.userFields;5, --palette--;;6, --palette--;;7, --palette--;;8,--palette--;;9, --palette--;;10, --palette--;;11, --palette--;;12, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.applicationData, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_articles.applicationData;13, --palette--;;14, --palette--;;15')
    ),
    'palettes' => array(
        '1' => array(
            'showitem' => 'art_no, description',
            'canNotCollapse' => true,
        ),
        '2' => array(
            'showitem' => 'quantity, price_calc_qty',
            'canNotCollapse' => true,
        ),
        '3' => array(
            'showitem' => 'fixedCost1, fixedCost2',
            'canNotCollapse' => true,
        ),
        '4' => array(
            'showitem' => 'price_net, price_gross',
            'canNotCollapse' => true,
        ),
        '5' => array(
            'showitem' => 'userField01',
            'canNotCollapse' => true,
        ),
        '6' => array(
            'showitem' => 'userField02',
            'canNotCollapse' => true,
        ),
        '7' => array(
            'showitem' => 'userField03',
            'canNotCollapse' => true,
        ),
        '8' => array(
            'showitem' => 'userField04',
            'canNotCollapse' => true,
        ),
        '9' => array(
            'showitem' => 'userField05',
            'canNotCollapse' => true,
        ),
        '10' => array(
            'showitem' => 'userField06',
            'canNotCollapse' => true,
        ),
        '11' => array(
            'showitem' => 'userField07',
            'canNotCollapse' => true,
        ),
        '12' => array(
            'showitem' => 'userField08',
            'canNotCollapse' => true,
        ),
        '13' => array(
            'showitem' => 'applSpecData',
            'canNotCollapse' => true,
        ),
        '14' => array(
            'showitem' => 'applSpecDataClass',
            'canNotCollapse' => true,
        ),
        '15' => array(
            'showitem' => 'artrelApplSpecUid',
            'canNotCollapse' => true,
        ),
        '16' => array(
            'showitem' => 'artrelApplIdentifier',
            'canNotCollapse' => true,
        ),
        '17' => array(
            'showitem' => 'tax_code, tax_percentage',
            'canNotCollapse' => true,
        ),
        '18' => array(
            'showitem' => 'price_category',
            'canNotCollapse' => true,
        ),
    )
);



$TCA['tx_ptgsashop_orders_dispatchcost'] = array(
    'ctrl' => $TCA['tx_ptgsashop_orders_dispatchcost']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_cruser_id,orders_id,deliveries_id,cost_type_name,cost_comp_1,cost_comp_2,cost_comp_3,cost_comp_4,allowance_comp_1,allowance_comp_2,allowance_comp_3,allowance_comp_4,cost_tax_code,tax_percentage,dispatch_cost'
    ),
    'feInterface' => $TCA['tx_ptgsashop_orders_dispatchcost']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_cruser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.fe_cruser_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'orders_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.orders_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'deliveries_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.deliveries_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int,nospace',
            )
        ),
        'cost_type_name' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.cost_type_name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'cost_comp_1' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.cost_comp_1',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'cost_comp_2' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.cost_comp_2',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'cost_comp_3' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.cost_comp_3',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'cost_comp_4' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.cost_comp_4',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'allowance_comp_1' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.allowance_comp_1',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'allowance_comp_2' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.allowance_comp_2',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'allowance_comp_3' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.allowance_comp_3',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'allowance_comp_4' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.allowance_comp_4',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
        'cost_tax_code' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.cost_tax_code',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '2',
                'eval' => 'trim,nospace',
            )
        ),
        'tax_percentage' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.tax_percentage',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'double2',
            )
        ),
        'dispatch_cost' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_dispatchcost.dispatch_cost',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '15',
                'eval' => 'nospace',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'cost_type_name, --palette--;;1, --palette--;;2, cost_tax_code, tax_percentage, dispatch_cost')
    ),
    'palettes' => array(
        '1' => array(
            'showitem' => 'cost_comp_1, cost_comp_2, cost_comp_3, cost_comp_4',
            'canNotCollapse' => true,
        ),
        '2' => array(
            'showitem' => 'allowance_comp_1, allowance_comp_2, allowance_comp_3, allowance_comp_4',
            'canNotCollapse' => true,
        ),
    )
);



$TCA['tx_ptgsashop_orders_paymentmethods'] = array(
    'ctrl' => $TCA['tx_ptgsashop_orders_paymentmethods']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_cruser_id,orders_id,method,epayment_success,epayment_trans_id,epayment_ref_id,epayment_short_id,bank_account_holder,bank_name,bank_account_number,bank_code,bank_bic,bank_iban,gsa_dta_acc_no'
    ),
    'feInterface' => $TCA['tx_ptgsashop_orders_paymentmethods']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_cruser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.fe_cruser_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'orders_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.orders_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'method' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.method',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('LLL:EXT:pt_gsauserreg/pi1/locallang.xml:pm_invoice', 'bt'),
                    array('LLL:EXT:pt_gsauserreg/pi1/locallang.xml:pm_debit', 'dd'),
                    array('LLL:EXT:pt_gsauserreg/pi1/locallang.xml:pm_ccard', 'cc'),
                ),
                'maxitems' => 1,
                'size' => 1,
                'eval' => 'required,trim',
            )
        ),
        'epayment_success' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.epayment_success',
            'config' => array(
                'type' => 'check',
            )
        ),
        'epayment_trans_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.epayment_trans_id',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
            )
        ),
        'epayment_ref_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.epayment_ref_id',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
            )
        ),
        'epayment_short_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.epayment_short_id',
            'config' => array(
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
            )
        ),
        'bank_account_holder' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.bank_account_holder',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '27',
                'eval' => 'trim',
            )
        ),
        'bank_name' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.bank_name',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '40',
                'eval' => 'trim',
            )
        ),
        'bank_account_number' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.bank_account_number',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '30',
                'eval' => 'trim',
            )
        ),
        'bank_code' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.bank_code',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '10',
                'eval' => 'trim',
            )
        ),
        'bank_bic' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.bank_bic',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '11',
                'eval' => 'trim',
            )
        ),
        'bank_iban' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.bank_iban',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '34',
                'eval' => 'trim',
            )
        ),
        'gsa_dta_acc_no' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.gsa_dta_acc_no',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '3',
                'eval' => 'int',
            )
        ),
    ),
    'types' => array(
        'cc' => array('showitem' => 'method, gsa_dta_acc_no, --div--;LLL:EXT:pt_gsauserreg/pi1/locallang.xml:pm_ccard, epayment_success, epayment_trans_id, epayment_ref_id, epayment_short_id'),
        'dd' => array('showitem' => 'method, gsa_dta_acc_no, --div--;LLL:EXT:pt_gsauserreg/pi1/locallang.xml:pm_debit, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.accountData;1, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.bankData;2, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_orders_paymentmethods.bicIban;3, --palette--;;4 '),
        'bt' => array('showitem' => 'method, gsa_dta_acc_no'),
    ),
    'palettes' => array(
        '1' => array(
        	'showitem' => 'bank_account_holder, bank_account_number',
            'canNotCollapse' => true,
        ),
        '2' => array(
        	'showitem' => 'bank_name, bank_code',
            'canNotCollapse' => true,
        ),
        '3' => array(
        	'showitem' => 'bank_bic',
            'canNotCollapse' => true,
        ),
        '4' => array(
        	'showitem' => 'bank_iban',
            'canNotCollapse' => true,
        ),
    )
);



$TCA['tx_ptgsashop_order_wrappers'] = array(
    'ctrl' => $TCA['tx_ptgsashop_order_wrappers']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_cruser_id,customer_id,related_doc_no,orders_id,order_timestamp,sum_net,sum_gross,wf_status_code,wf_lock_userid,wf_lock_timestamp,wf_lastuser_id'
    ),
    'feInterface' => $TCA['tx_ptgsashop_order_wrappers']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_cruser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.fe_cruser_id',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'size' => '1',
                'maxitems' => '1',
                'eval' => 'required,int,nospace',
            )
        ),
        'customer_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.customer_id',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '11',
                'eval' => 'required,int',
            )
        ),
        'related_doc_no' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.related_doc_no',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'orders_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.orders_id',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_ptgsashop_orders',
                'maxitems' => 1,
                'appearance' => array(
                    'newRecordLinkPosition' => 'none',
                    'collapseAll' => false,
                ),
                #'pass_content' => 1,
            )
        ),
        'order_timestamp' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.order_timestamp',
            'config' => array(
                'type' => 'input',
                'size' => '12',
                'max' => '20',
                'eval' => 'datetime,required',
                'checkbox' => '0',
                'default' => '0',
            )
        ),
        'sum_net' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.sum_net',
            'config' => array(
                'type' => 'input',
                'size' => '12',
                'max' => '12',
                'eval' => 'required,trim',
            )
        ),
        'sum_gross' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.sum_gross',
            'config' => array(
                'type' => 'input',
                'size' => '12',
                'max' => '12',
                'eval' => 'required,trim',
            )
        ),
        'wf_status_code' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.wf_status_code',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '2',
                'range' => array('lower'=>0,'upper'=>99),
                'eval' => 'required,int',
            )
        ),
        'wf_lastuser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.wf_lastuser_id',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
        'wf_lock_userid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.wf_lock_userid',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
        'wf_lock_timestamp' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.wf_lock_timestamp',
            'config' => array(
                'type' => 'input',
                'size' => '12',
                'max' => '20',
                'eval' => 'datetime',
                'checkbox' => '0',
                'default' => '0'
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden, fe_cruser_id, customer_id, related_doc_no, order_timestamp, --palette--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.prices;1, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.workflow, wf_status_code, wf_lastuser_id, wf_lock_userid, wf_lock_timestamp, --div--;LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_order_wrappers.order, orders_id')
    ),
    'palettes' => array(
        '1' => array(
            'showitem' => 'sum_net, sum_gross',
            'canNotCollapse' => true,
        ),
    )
);



$TCA['tx_ptgsashop_workflow'] = array(
    'ctrl' => $TCA['tx_ptgsashop_workflow']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,status_code,name,description,auth_groups_view,auth_groups_use,update_order,condition_method,permission_method,approve_action_method,deny_action_method,advance_action_method,halt_action_method,approve_status_code,deny_status_code,advance_status_code,label_choice,label_approve,label_deny,label_confirm_approve,label_confirm_deny,sys_language_uid'
    ),
    'feInterface' => $TCA['tx_ptgsashop_workflow']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'status_code' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.status_code',
            'config' => array(
                'type' => 'input',
                'size' => '3',
                'max' => '2',
                'range' => array('lower'=>0,'upper'=>99),
                'eval' => 'required,int',
            )
        ),
        'name' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'description' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.description',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'auth_groups_view' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.auth_groups_view',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_groups',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 100,
            )
        ),
        'auth_groups_use' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.auth_groups_use',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'fe_groups',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 100,
            )
        ),
        'update_order' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.update_order',
            'config' => array(
                'type' => 'check',
                'default' => '1'
            )
        ),
        'condition_method' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.condition_method',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'permission_method' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.permission_method',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'approve_action_method' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.approve_action_method',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'deny_action_method' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.deny_action_method',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'advance_action_method' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.advance_action_method',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'halt_action_method' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.halt_action_method',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'approve_status_code' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.approve_status_code',
            'config' => array(
                'type' => 'input',
                'size' => '3',
                'max' => '2',
                'range' => array('lower'=>0,'upper'=>99),
                'eval' => 'trim',
            )
        ),
        'deny_status_code' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.deny_status_code',
            'config' => array(
                'type' => 'input',
                'size' => '3',
                'max' => '2',
                'range' => array('lower'=>0,'upper'=>99),
                'eval' => 'trim',
            )
        ),
        'advance_status_code' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.advance_status_code',
            'config' => array(
                'type' => 'input',
                'size' => '3',
                'max' => '2',
                'range' => array('lower'=>0,'upper'=>99),
                'eval' => 'trim',
            )
        ),
        'label_choice' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.label_choice',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'label_approve' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.label_approve',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'label_deny' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.label_deny',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'label_confirm_approve' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.label_confirm_approve',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'label_confirm_deny' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_workflow.label_confirm_deny',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
                )
            )
        ),
        'l18n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_ptgsashop_workflow',
                'foreign_table_where' => 'tx_ptgsashop_workflow.sys_language_uid IN (-1,0)',
                #'foreign_table_where' => 'AND tx_ptgsashop_workflow.uid=###REC_FIELD_l18n_parent### AND tx_ptgsashop_workflow.sys_language_uid IN (-1,0)'  // for usage with seperate language labels table
            )
       ),
       'l18n_diffsource' => array(
            'config' => array(
                 'type'=>'passthrough'
            )
       ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden;;1;;1-1-1, status_code, name, description, auth_groups_view, auth_groups_use, update_order, condition_method, permission_method, approve_action_method, deny_action_method, advance_action_method, halt_action_method, approve_status_code, deny_status_code, advance_status_code, label_choice, label_approve, label_deny, label_confirm_approve, label_confirm_deny, sys_language_uid')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);


$TCA['tx_ptgsashop_amendmentlog'] = array(
    'ctrl' => $TCA['tx_ptgsashop_amendmentlog']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,fe_cruser_id,order_wrapper_id,log_entry,status_prev,status_new'
    ),
    'feInterface' => $TCA['tx_ptgsashop_amendmentlog']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'fe_cruser_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_amendmentlog.fe_cruser_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'order_wrapper_id' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_amendmentlog.order_wrapper_id',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'required,int,nospace',
            )
        ),
        'log_entry' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_amendmentlog.log_entry',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            )
        ),
        'status_prev' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_amendmentlog.status_prev',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '2',
                'range' => array('lower'=>0,'upper'=>99),
                'eval' => 'int',
            )
        ),
        'status_new' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_amendmentlog.status_new',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '2',
                'range' => array('lower'=>0,'upper'=>99),
                'eval' => 'int',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden;;1;;1-1-1, fe_cruser_id, order_wrapper_id, log_entry, status_prev, status_new')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);


$TCA['tx_ptgsashop_artrel'] = array(
    'ctrl' => $TCA['tx_ptgsashop_artrel']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,gsa_art_nummer,max_amount,exclusion_articles,required_articles,related_articles,bundled_articles,appl_spec_uid, appl_identifier'
    ),
    'feInterface' => $TCA['tx_ptgsashop_artrel']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'gsa_art_nummer' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel.gsa_art_nummer',
            'config' => array(
                'type' => 'input',
                'size' => '6',
                'max' => '6',
                'eval' => 'required,int,nospace,unique',
            )
        ),
        'max_amount' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel.max_amount',
            'config' => array(
                'type' => 'input',
                'size' => '6',
                'max' => '6',
                'eval' => 'int',
            )
        ),
        'exclusion_articles' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel.exclusion_articles',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,nospace',
            )
        ),
        'required_articles' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel.required_articles',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,nospace',
            )
        ),
        'related_articles' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel.related_articles',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,nospace',
            )
        ),
        'bundled_articles' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel.bundled_articles',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,nospace',
            )
        ),
        'appl_spec_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel.appl_spec_uid',
            'config' => array(
                'type' => 'input',
                'size' => '6',
                'max' => '6',
                'eval' => 'int',
            )
        ),
        'appl_identifier' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_artrel.appl_identifier',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,nospace',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden;;1;;1-1-1, gsa_art_nummer, max_amount, exclusion_articles, required_articles, related_articles, bundled_articles, appl_spec_uid, appl_identifier')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);




$TCA['tx_ptgsashop_article_images'] = array(
    'ctrl' => $TCA['tx_ptgsashop_article_images']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,gsa_art_nummer,path,description,alt,title'
    ),
    'feInterface' => $TCA['tx_ptgsashop_order_wrappers']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'gsa_art_nummer' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_article_images.gsa_art_nummer',
            'config' => array(
                'type' => 'input',
                'size' => '6',
                'max' => '6',
                'eval' => 'required,int,nospace,unique',
            )
        ),
        'path' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_article_images.path',		
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'disallowed' => '*',
				'show_thumbs' => 1,
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'max_size' => '10000',
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => '1',
			)
        ),
        'description' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_article_images.description',
            'config' => array(
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'eval' => 'trim',
            )
        ),
        'alt' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_article_images.alt',
            'config' => array(
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'eval' => 'trim',
            )
        ),
        'title' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_article_images.title',
            'config' => array(
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'eval' => 'trim',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden;;1;;1-1-1, gsa_art_nummer, path, description, alt, title')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);


$TCA['tx_ptgsashop_cache_articles'] = array(
    'ctrl' => $TCA['tx_ptgsashop_cache_articles']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'hidden,gsadb_artnr,gsadb_match,gsadb_match2'
    ),
    'feInterface' => $TCA['tx_ptgsashop_cache_articles']['feInterface'],
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'gsadb_artnr' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_cache_articles.gsadb_artnr',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '120',
                'eval' => 'required',
                'checkbox' => '0',
            )
        ),
        'gsadb_match' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_cache_articles.gsadb_match',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '255',
                'eval' => 'required',
                'checkbox' => '0',
            )
        ),
        'gsadb_match2' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_cache_articles.gsadb_match2',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '60',
                'eval' => 'required',
                'checkbox' => '0',
            )
        ),
        'gsadb_passiv' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:pt_gsashop/locallang_db.xml:tx_ptgsashop_cache_articles.gsadb_passiv',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'gsadb_artnr;;1;;1-1-1, gsadb_match, gsadb_match2, gsadb_passiv')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);



?>