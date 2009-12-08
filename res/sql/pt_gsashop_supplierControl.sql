/**
 * Table structure for GSA user table `pt_gsashop_supplierControl` to be used in the separate ERP database GSMAND<n>.
 * 
 * This table is located in the GSA database to allow NULL fields and SQL join statements using this table combined with other GSA database tables.
 * 
 * Note: This SQL script is not required if you're using one of the SQL scripts provided with the extension pt_gsaminidb!
 */


-- DROP TABLE IF EXISTS pt_gsashop_supplierControl;

CREATE TABLE pt_gsashop_supplierControl (
	uid INTEGER auto_increment,
	invoiceDocNumber VARCHAR(25),
	bookingDate DATE,
	articleUid INTEGER(11),
	articleNumber VARCHAR(120),
	articleQty INTEGER(11),
	supplierUid INTEGER(11),
	supplierArticleNumber VARCHAR(40),
	supplierEanNumber VARCHAR(20),
	supplierUnitPrice1 DOUBLE(14,4),
	supplierUnitPrice2 DOUBLE(14,4),
	supplierUnitPrice3 DOUBLE(14,4),
	supplierDiscount1 DOUBLE(14,4),
	supplierDiscount2 DOUBLE(14,4),
	supplierDiscount3 DOUBLE(14,4),
	isGrossPrice TINYINT(3),
	PRIMARY KEY (uid)
);

