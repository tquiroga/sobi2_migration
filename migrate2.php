<?php
//
/////////////////////////////////////////////////////////////////
//
// EDIT AND/OR REVIEW EVERYTHING IN THIS AREA BEFORE RUNNING
//
/////////////////////////////////////////////////////////////////
//
// Your mysql host and login information: 
$host = "localhost";
$user = "root";
$password = "c@BB@g3s";
$database = "TEST";

// IMPORTANT -- README
//
// The way this script works is quite simple.  You copy the old SOBI2 tables into the
// SAME DATABASE as the new sobipro tables.  If you're upgrading Joomla from 1.x to
// 1.6 then your original tables likely match the default below.
//
// Put the "old" and "new" table prefixes in the next two variables.  The script will
// read from the OPREFIX tables and insert (write) new data into the NPREFIX tables.
//
// You should backup your data because your import may go wrong, you may need to 
// tweak the script or account for things I hadn't seen coming. 
//
// To run this script ssh into your host and type php <scriptname> at the command
// line.  If you don't have shell access, uploaded it with a .php extension and
// point a web browser at it.  When its done review all of your categories and
// some of your more complex entries to see if it worked right.
//
// THIS IS NOT A PLUGIN FOR SOBI2 OR SOBIPRO.  IT RUNS INDEPENDENTLY.
//
// This was meant to be run from the command line, so it will likely look like
// crap from a web browser, but it should still do what it was meant to do.
//
// HINT: Because this script is smart enough to re-map your old entries to new
// assignments and sections you can run it over and over and over to get it to
// work.  It just keeps making new Sections within SobiPro.  
//
// Once you get it working, remove/re-install sobipro and run it once
// more to get a fresh migration.  You can import multiple SOBI2 installs into
// the same SobiPro by just changing the OPREFIX to match each SOBI2 table
// and then re-run the script.
//
$OPREFIX = "backup_jos_sobi2";
$NPREFIX = "wtmxg_sobipro";

$verbose=3;
// Set to 0 the script should only squak on errors or mysql bombing
// Set to 1 it will print basic progress
// Set to 2 it will show you all old/new category translation 
// Set to 3 it will print all of the option_selected rows it skips (exhaustive)

$insert=1;
// THIS DISABLES INSERTING AND JUST RUNS THE SELECT QUERIES IF 0:
// note: A lot of queries are dependant upon a result from an insert,
// for instance to get back the insert_id() - so you may see errors
// or script confusion if you turn off insert and just watch the sql
// it generates -- it won't know what auto_increments were assigned
// to something we didn't insert. 

$debugsql=0;
// THIS PRINTS OUT THE SQL INSERTS TO YOUR SCREEN. Turn this ON (1) with the above set to 0
// if you want to see what the SQL output will look like.  Use | more - its a mess.

$section['owner'] = 30;
// Default owner in cases where we can't determine it
// Go into your SobiPro install, find your user id number and put it here.

// The default language - if you break this I've found stuff won't show up
$lang = "en-GB";



// YOU SHOULD NOT NEED TO EDIT ANYTHING BELOW THIS LINE UNLESS THE SCRIPT FAILS
//
// HOWEVER:  You may want to review the insert lines in the script to see
// what presumptions have been made, cases where I just insert a static value
// etc.  Sometimes all zero dates are inserted, other times I found a valid
// original and carry it over. 
//
// Sometimes, in some date/time fields, I just tell mysql to use NOW() as
// the date - you should be aware of that if dates are really important
//
// In some areas, I try to munge up titles to remove leading spaces or all 
// caps entries. You can search the script for "Assumption" to find comments
//
// If you didn't backup your database and you ran some random code off the 'net
// against your data you're a complete idiot. 
//
/////////////////////////////////////////////////////////////////////////////////

// p.s. - this code is absolute crap, I know.  Make it better.  It works...


// Setup a connect to mysql:
$connection = mysql_connect($host,$user,$password)
        or die("Could not connect: ".mysql_error());
mysql_select_db($database,$connection)
        or die("Error in selecting the database:".mysql_error());

// This will create a new section in your SobiPro install:
// Need to insert rows into these tables:
//
//  _object (1 row) -	ID is auto_increment. This table's auto_increment assigns us
//			a new id for our section. 
//  _field (1 row)	 
//  _config (2 rows)
//  _language (5 rows)	3 associated by fid, 2 by id field
//  _relations (1 row)
// 
// permissions:  (a lot of assumptions were made here, may not be 100% accurate)
//  _permissions_groups - 2 rows
//  _permissions_map - 4 rows
//  _permissions_rules - 1 row

// Get the old name of the original SOBI2 installation 
//
$sql = "SELECT * FROM ".$OPREFIX."_config";
$getname = dosql($sql);

while($row=mysql_fetch_array($getname)) {

	$ckey = $row['configKey'];
	$value = $row['configValue'];

	if ($ckey == 'componentName') { $section['name'] = $value; }
	}

	if (!$section['name']) { $section['name'] = "A new SobiPro Section!/n"; }

// convert the section into an underscored sentence
$title = $section['name'];
$title = mkhyphen($title);

// this is going into the _object table (1 row):
$sql = "INSERT INTO `".$NPREFIX."_object` (`nid`, `name`, `approved`, `confirmed`, `counter`, `cout`, `coutTime`, `createdTime`, `defURL`, `metaDesc`, `metaKeys`, `metaAuthor`, `metaRobots`, `options`, `oType`, `owner`, `ownerIP`, `params`, `parent`, `state`, `stateExpl`, `updatedTime`, `updater`, `updaterIP`, `validSince`, `validUntil`, `version`) VALUES ('$title', '".$section['name']."', 1, 1, 0, 0, '0000-00-00 00:00:00', NOW(), '', '', '', '', '', '', 'section', 0, '127.0.0.1', '', 0, 1, '', NOW(), 0, '127.0.0.1', NOW(), '0000-00-00 00:00:00', 1)";

$sql_result = dosql($sql);
$section["id"] = mysql_insert_id();

// this defines the 'name' field that is standard with each section instance
$sql = "INSERT INTO `".$NPREFIX."_field` (`nid`, `adminField`, `admList`, `dataType`, `enabled`, `fee`, `fieldType`, `filter`, `isFree`, `position`, `priority`, `required`, `section`, `multiLang`, `uniqueData`, `validate`, `addToMetaDesc`, `addToMetaKeys`, `editLimit`, `editable`, `showin`, `inSearch`, `withLabel`, `parse`, `version`) VALUES ('field_name', 0, 0, 0, 1, 0, 'inbox', '', 1, 1, 0, 1, '".$section['id']."', 0, 0, 0, 0, 0, -1, 1, 'both', 1, 1, 0, 1)"; 

$sql_result = dosql($sql);
$section['namefield'] = mysql_insert_id();

// this creations a 'section' relation in the _relations table 
$sql = "INSERT INTO `".$NPREFIX."_relations` (`id`, `pid`, `oType`, `position`, `validSince`, `validUntil`, `copy`) VALUES ('".$section['id']."', 0, 'section', 1, NOW(), '0000-00-00 00:00:00', 0)"; 

$sql_result = dosql($sql);

// this creates numerous rows in the _config table for the section
// The allowed tags is an example of something I just copied off
// a sample insert in a new SobiPro. Review your post-import result. 

$sql = "INSERT INTO `".$NPREFIX."_config` (`sKey`, `sValue`, `section`, `critical`, `cSection`) VALUES
('name_field', '".$section['namefield']."', ".$section['id'].", 0, 'entry'),
('entries_ordering', 'field_name', ".$section['id'].", 0, 'list'),
('always_add_section', '0', ".$section['id'].", 0, 'meta'),
('maxCats', '5', ".$section['id'].", 0, 'entry'),
('publish_limit', '0', ".$section['id'].", 0, 'entry'),
('show_icon', '1', ".$section['id'].", 0, 'category'),
('show_intro', '1', ".$section['id'].", 0, 'category'),
('show_desc', '1', ".$section['id'].", 0, 'category'),
('parse_desc', '1', ".$section['id'].", 0, 'category'),
('allowed_tags_array', 'YToxNzp7aTowO3M6MToiYSI7aToxO3M6MToicCI7aToyO3M6MjoiYnIiO2k6MztzOjI6ImhyIjtpOjQ7czozOiJkaXYiO2k6NTtzOjI6ImxpIjtpOjY7czoyOiJ1bCI7aTo3O3M6NDoic3BhbiI7aTo4O3M6NToidGFibGUiO2k6OTtzOjI6InRyIjtpOjEwO3M6MjoidGQiO2k6MTE7czozOiJpbWciO2k6MTI7czoyOiJoMSI7aToxMztzOjI6ImgyIjtpOjE0O3M6MjoiaDMiO2k6MTU7czoyOiJoNCI7aToxNjtzOjI6Img1Ijt9', ".$section['id'].", 0, 'html'),
('allowed_attributes_array', 'YTo4OntpOjA7czo1OiJjbGFzcyI7aToxO3M6MjoiaWQiO2k6MjtzOjU6InN0eWxlIjtpOjM7czo0OiJocmVmIjtpOjQ7czozOiJzcmMiO2k6NTtzOjQ6Im5hbWUiO2k6NjtzOjM6ImFsdCI7aTo3O3M6NToidGl0bGUiO30=', ".$section['id'].", 0, 'html'),
('template', 'default', ".$section['id'].", 0, 'section'),
('top_menu', '1', ".$section['id'].", 0, 'general'),
('parse_template_content', '0', ".$section['id'].", 0, 'general'),
('categories_in_line', '2', ".$section['id'].", 0, 'list'),
('categories_ordering', 'name.desc', ".$section['id'].", 0, 'list'),
('cat_desc', '0', ".$section['id'].", 0, 'list'),
('cat_meta', '0', ".$section['id'].", 0, 'list'),
('subcats', '1', ".$section['id'].", 0, 'list'),
('num_subcats', '6', ".$section['id'].", 0, 'list'),
('entries_limit', '8', ".$section['id'].", 0, 'list'),
('entries_in_line', '1', ".$section['id'].", 0, 'list'),
('entry_meta', '0', ".$section['id'].", 0, 'list'),
('entry_cats', '1', ".$section['id'].", 0, 'list'),
('show', '1', ".$section['id'].", 0, 'alphamenu'),
('letters', 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0-9', ".$section['id'].", 0, 'alphamenu'),
('verify', '1', ".$section['id'].", 0, 'alphamenu'),
('primary_field', '".$section['namefield']."', ".$section['id'].", 0, 'alphamenu'),
('extra_fields_array', '', ".$section['id'].", 0, 'alphamenu'),
('entry_enabled', '0', ".$section['id'].", 0, 'redirects'),
('entry_url', 'index.php', ".$section['id'].", 0, 'redirects'),
('entry_msgtype', 'error', ".$section['id'].", 0, 'redirects'),
('entry_msg', 'UNAUTHORIZED_ACCESS', ".$section['id'].", 0, 'redirects'),
('category_enabled', '0', ".$section['id'].", 0, 'redirects'),
('category_url', 'index.php', ".$section['id'].", 0, 'redirects'),
('category_msgtype', 'error', ".$section['id'].", 0, 'redirects'),
('category_msg', 'UNAUTHORIZED_ACCESS', ".$section['id'].", 0, 'redirects'),
('section_enabled', '0', ".$section['id'].", 0, 'redirects'),
('section_url', 'index.php', ".$section['id'].", 0, 'redirects'),
('section_msgtype', 'error', ".$section['id'].", 0, 'redirects'),
('section_msg', 'UNAUTHORIZED_ACCESS', ".$section['id'].", 0, 'redirects')";

$sql_result = dosql($sql);

// _language insert
//
$sql = "INSERT INTO `".$NPREFIX."_language` (`sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`, `params`, `options`, `explanation`) VALUES ('suffix', '', NULL, '$lang', 'field', '".$section['namefield']."', 0, NULL, NULL, NULL), ('description', '', NULL, '$lang', 'field', '".$section['namefield']."', 0, NULL, NULL, NULL), ('name', 'Name', NULL, '$lang', 'field', '".$section['namefield']."', 0, NULL, NULL, NULL), ('name', '".$section['name']."', NULL, '$lang', 'section', 0, '".$section['id']."', NULL, NULL, NULL), ('description', '', NULL, '$lang', 'section', 0, '".$section['id']."', NULL, NULL, NULL)";

$sql_result = dosql($sql); 

// insert a row into the permissions_rules for the section
//
$sql = "INSERT INTO `".$NPREFIX."_permissions_rules` (`name`, `nid`, `validSince`, `validUntil`, `note`, `state`) VALUES ('".$section['name']."', '$title', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Default permissions for the section \"".$section['name']."\"', 1)";

$sql_result = dosql($sql);
$section['ruleid'] = mysql_insert_id();

// insert rows into the map
$sql = "INSERT INTO `".$NPREFIX."_permissions_map` (`rid`, `sid`, `pid`) VALUES ('".$section['ruleid']."', '".$section['id']."', 4), ('".$section['ruleid']."', '".$section['id']."', 7), ('".$section['ruleid']."', '".$section['id']."', 10), ('".$section['ruleid']."', '".$section['id']."', 16)"; 

$sql_result = dosql($sql);

$sql = "INSERT INTO `".$NPREFIX."_permissions_groups` (`rid`, `gid`) VALUES ('".$section['ruleid']."', 0), ('".$section['ruleid']."', 2)";

$sql_result = dosql($sql);

if ($verbose) { print "Info: Inserted ".$section['name']." as $title/n";
		print "Info: Section ID: ".$section['id'].", name field: ".$section['namefield']."/n";
	      } 

// Create the categories 
//
// This will loop through every category in your SOBI2 install
// and create a new category, and associate the new numbering
// to old numbering and then associate entries and fields as well...

$sql = "SELECT * FROM `".$OPREFIX."_categories";
$loop_result = dosql($sql);

// loop through all of the categories:
//
while($row=mysql_fetch_array($loop_result)) {

$catid=$row['catid'];
$name=$row['name'];
$image=$row['image'];
$image_position=$row['image_position'];
$description=$row['description'];
$introtext=$row['introtext'];
$published=$row['published'];
$checked_out=$row['checked_out_time'];
$ordering=$row['ordering'];
$access=$row['access'];
$count=$row['count'];
$params=$row['params'];
$icon=$row['icon'];

// 1 entry in _object to obtain the new category id.  Parent is ".$section['id']."
// 1 entry in _category by new id.
// 1 entry in _relations by new id refering to section[id]
// 3 entries in _language by new id.

// _object table data and retrieve our new category id:
//

// this routine turns a sentence into_a_hyphenated_sentence
$newcatalias = mkhyphen($name);

$sql= "INSERT INTO `".$NPREFIX."_object` (`nid`, `name`, `approved`, `confirmed`, `counter`, `cout`, `coutTime`, `createdTime`, `defURL`, `metaDesc`, `metaKeys`, `metaAuthor`, `metaRobots`, `options`, `oType`, `owner`, `ownerIP`, `params`, `parent`, `state`, `stateExpl`, `updatedTime`, `updater`, `updaterIP`, `validSince`, `validUntil`, `version`) VALUES ('$newcatalias', '$name', 1, 0, '$count', 0, '0000-00-00 00:00:00', NOW(), '', '', '', '', '', '', 'category', '".$section['owner']."', '127.0.0.1', '', '".$section['id']."', 1, '', NOW(), '".$section['owner']."', '127.0.0.1', NOW(), '0000-00-00 00:00:00', 1)"; 

$sql_result = dosql($sql);
$newcatid = mysql_insert_id();

// This creates a [new]-> old category reference for later use below
// 
$cat[$catid] = $newcatid;

if ($verbose > 1) { print "detail:: category $catid is now $cat[$catid] /n"; }

// _category table data:
//
$sql = "INSERT INTO `".$NPREFIX."_category` (`id`, `position`, `description`, `parseDesc`, `introtext`, `showIntrotext`, `icon`, `showIcon`) VALUES ('$newcatid', 0, '$description', '2', '$introtext', '2', '$icon', '2')"; 

$sql_result = dosql($sql);

// _relations table data:
//
$sql = "INSERT INTO `".$NPREFIX."_relations` (`id`, `pid`, `oType`, `position`, `validSince`, `validUntil`, `copy`) VALUES ('$newcatid', '".$section['id']."', 'category', 1, NOW(), '0000-00-00 00:00:00', 0)"; 

$sql_result = dosql($sql);

// _language data
//
$sql = "INSERT INTO `".$NPREFIX."_language` (`sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`, `params`, `options`, `explanation`) VALUES ('name', '$name', NULL, '$lang', 'category', 0, '$newcatid', NULL, NULL, NULL), ('introtext', 'This is the new category introtext', NULL, '$lang', 'category', 0, '$newcatid', NULL, NULL, NULL), ('description', '$description', NULL, '$lang', 'category', 0, '$newcatid', NULL, NULL, NULL)"; 

$sql_result = dosql($sql);

} 

//
// ...this is the end of while loop for creating all of your categories



// Migrate all of the "Custom Fields" from SOBI2 to SobiPro 
//
// If you ran the script that would mass-hack a SOBI2 install so that
// you could run multiple instances in the same joomla it may have
// renamed the field names in the _language table. 
//
// ...we need to know if that is the case:
//
$sql = "DESCRIBE ".$OPREFIX."_language";

$col_result = dosql($sql);
$pos=1;

while($row = mysql_fetch_array($col_result)) {
   
    if ($pos == '4') { $langsec = $row['Field']; }
    if ($pos == '6') { $langlang = $row['Field']; }
    $pos++;
}



// massive query to get all field data for "fields" into one set 
//
$sql = "SELECT ".$OPREFIX."_language.langKey, ".$OPREFIX."_language.langValue, ".$OPREFIX."_language.description, ".$OPREFIX."_language.".$langsec.", ".$OPREFIX."_language.fieldid, ".$OPREFIX."_language.".$langlang.", ".$OPREFIX."_fields.fieldType, ".$OPREFIX."_fields.wysiwyg, ".$OPREFIX."_fields.fieldDescription, ".$OPREFIX."_fields.explanation, ".$OPREFIX."_fields.is_free, ".$OPREFIX."_fields.payment, ".$OPREFIX."_fields.fieldChars, ".$OPREFIX."_fields.FieldRows, ".$OPREFIX."_fields.fieldColumns, ".$OPREFIX."_fields.preferred_size, ".$OPREFIX."_fields.CSSclass, ".$OPREFIX."_fields.enabled, ".$OPREFIX."_fields.isEditable, ".$OPREFIX."_fields.is_required, ".$OPREFIX."_fields.in_promoted, ".$OPREFIX."_fields.in_vcard, ".$OPREFIX."_fields.in_details, ".$OPREFIX."_fields.position, ".$OPREFIX."_fields.in_search, ".$OPREFIX."_fields.with_label, ".$OPREFIX."_fields.in_newline, ".$OPREFIX."_fields.isUrl, ".$OPREFIX."_fields.checked_out, ".$OPREFIX."_fields.checked_out_time, ".$OPREFIX."_fields.displayed FROM ".$OPREFIX."_language, ".$OPREFIX."_fields WHERE ".$OPREFIX."_fields.fieldid = ".$OPREFIX."_language.fieldid AND ".$OPREFIX."_language.".$langsec." = 'fields' AND ".$OPREFIX."_language.".$langlang." = 'english'";

$field_result = dosql($sql);

if ($verbose) { print "Status: Inserting fields into _language and _field tables.../n"; }

while($field = mysql_fetch_array($field_result)) {

// This is an attempt to map old field types to new.  I
// couldn't find anything definitive, so make sure you 
// don't have any of these not mapped...

$fieldtype = "WHAT SHOULD I BE? I was: ".$field['fieldType'];;
if ($field['fieldType'] == '1') { $fieldtype = "inbox"; }
if ($field['fieldType'] == '2') { $fieldtype = "textarea"; }
if ($field['fieldType'] == '6') { $fieldtype = "chbxgroup"; }
if ($field['fieldType'] == '3') { $fieldtype = "radio"; }
if ($field['fieldType'] == '5') { $fieldtype = "select"; }

// I'm not sure what to do with custom variables:
//
if ($field['fieldType'] == '4') { $fieldtype = "textarea"; }


$sql = "INSERT INTO `".$NPREFIX."_field` (`nid`, `adminField`, `admList`, `dataType`, `enabled`, `fee`, `fieldType`, `filter`, `isFree`, `position`, `priority`, `required`, `section`, `multiLang`, `uniqueData`, `validate`, `addToMetaDesc`, `addToMetaKeys`, `editLimit`, `editable`, `showIn`, `allowedAttributes`, `allowedTags`, `editor`, `inSearch`, `withLabel`, `cssClass`, `parse`, `template`, `notice`, `params`, `defaultValue`, `version`) VALUES ('".$field['langKey']."', 0, 0, 0, 1, 0, '$fieldtype', '0', '".$field['is_free']."', 2, 5, '".$field['is_required']."', '".$section['id']."', 0, 0, 0, 0, 0, -1, 1, 'both', '', '', '', 0, 1, '', 0, '', 'notices', 'YTo0OntzOjk6Im1heExlbmd0aCI7czozOiIxNTAiO3M6NToid2lkdGgiO3M6MzoiMzUwIjtzOjEyOiJzZWFyY2hNZXRob2QiO3M6NzoiZ2VuZXJhbCI7czoxNzoic2VhcmNoUmFuZ2VWYWx1ZXMiO3M6MDoiIjt9', '', 1)"; 


$sql_result = dosql($sql);
$newfieldid = mysql_insert_id();
$oldfield = $field['fieldid'];

// Create a matrix of old to new fieldid mapping for future queries: 
// 
// Basically, if our old fieldid was '1' it might be '66' now.

$fieldx[$oldfield] = $newfieldid;

// now we need to put this field into the _language table:
//
$sql = "INSERT INTO `".$NPREFIX."_language` (`sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`, `params`, `options`, `explanation`) VALUES ('name', '".$field['langValue']."', NULL, '$lang', 'field', '$newfieldid', 0, NULL, NULL, NULL), ('description', '".$field['description']."', NULL, '$lang', 'field', '$newfieldid', 0, NULL, NULL, NULL), ('suffix', '', NULL, '$lang', 'field', '$newfieldid', 0, NULL, NULL, NULL)"; 


$sql_result = dosql($sql);

}

// do this all over again for the multi-option selctable fields...
//
// massive query to get all field data for "fields" into one set
//
// NOTE: THIS QUERY LIMITS TO ENGLISH.  In my install of SOBI2 I
// found the same fields defined as both languages, so I limited. 
//
$sql = "SELECT ".$OPREFIX."_language.langKey, ".$OPREFIX."_language.langValue, ".$OPREFIX."_language.description, ".$OPREFIX."_language.".$langsec.", ".$OPREFIX."_language.fieldid, ".$OPREFIX."_language.".$langlang.", ".$OPREFIX."_fields.fieldType, ".$OPREFIX."_fields.wysiwyg, ".$OPREFIX."_fields.fieldDescription, ".$OPREFIX."_fields.explanation, ".$OPREFIX."_fields.is_free, ".$OPREFIX."_fields.payment, ".$OPREFIX."_fields.fieldChars, ".$OPREFIX."_fields.FieldRows, ".$OPREFIX."_fields.fieldColumns, ".$OPREFIX."_fields.preferred_size, ".$OPREFIX."_fields.CSSclass, ".$OPREFIX."_fields.enabled, ".$OPREFIX."_fields.isEditable, ".$OPREFIX."_fields.is_required, ".$OPREFIX."_fields.in_promoted, ".$OPREFIX."_fields.in_vcard, ".$OPREFIX."_fields.in_details, ".$OPREFIX."_fields.position, ".$OPREFIX."_fields.in_search, ".$OPREFIX."_fields.with_label, ".$OPREFIX."_fields.in_newline, ".$OPREFIX."_fields.isUrl, ".$OPREFIX."_fields.checked_out, ".$OPREFIX."_fields.checked_out_time, ".$OPREFIX."_fields.displayed FROM ".$OPREFIX."_language, ".$OPREFIX."_fields WHERE ".$OPREFIX."_fields.fieldid = ".$OPREFIX."_language.fieldid AND ".$OPREFIX."_language.".$langsec." = 'field_opt' AND ".$OPREFIX."_language.".$langlang." = 'english'  ";

$field_result = dosql($sql);

if ($verbose) { print "Status: Inserting multi-option fields into _language and _field_option tables.../n"; }	


$optpos = array();
while($field = mysql_fetch_array($field_result)) {

// we need to cross reference the old fieldid to the new fieldid:
$oldfield = $field['fieldid'];
$newfieldid = $fieldx[$oldfield];

// we need to add one row to _language for each row we find in the original
//
$sql = "INSERT INTO `".$NPREFIX."_language` (`sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`, `params`, `options`, `explanation`) VALUES ('".$field['langKey']."', '".$field['langValue']."', NULL, '$lang', 'field_option', '$newfieldid', 0, NULL, NULL, NULL)";


$sql_result = dosql($sql);

// this should create a sequential counter for each option
// for positioning...
//
if(isset($optpos[$newfieldid])) {
	$optpos[$newfieldid]++;
	// we need to add one row to _field_option for each row we find 
	$sql = "INSERT INTO `".$NPREFIX."_field_option` (`fid`, `optValue`, `optPos`, `img`, `optClass`, `actions`, `class`, `optParent`) VALUES ('$newfieldid', '".$field['langKey']."', '".$optpos[$newfieldid]."', '', '', '', '', '')";
	$sql_result = dosql($sql);
}
// else {
// 	//$sql = "INSERT INTO `".$NPREFIX."_field_option` (`fid`, `optValue`, `optPos`, `img`, `optClass`, `actions`, `class`, `optParent`) VALUES (NULL, '".$field['langKey']."', NULL, '', '', '', '', '')";
// }



}

// END OF THE FIELDS CODE



// Look in the original _item table and insert into various tables:
// Use our entry into _objects to create a cross reference and new numbering
//

$sql="SELECT * FROM ".$OPREFIX."_item";

$getitems = dosql($sql);
$sql_num=mysql_num_rows($getitems);

if ($verbose) {
print "Status: Found $sql_num entries in the old _item table.../n";
print "Status: ...inserting into the field_data, _language and _object tables.../n";
}

if ($verbose > 1) { print "Progress: "; }

$rowcnt=0;
$qtrdone = round($sql_num/4);
$halfdone = round($sql_num/2);
$thirddone = round($qtrdone*3);

while($row=mysql_fetch_array($getitems))
{

$itemid = $row['itemid'];
$title = $row['title'];
$publish_up = $row['publish_up'];
//$entry = $row['entry'];
$owner = $row['owner'];
$ip = $row['ip'];
$last_update = $row['last_update'];
$updating_user = $row['updating_user'];
$updating_ip = $row['updating_ip'];

$title = strtotitle($title);

$baseData = $title;
$baseData = ltrim($baseData);

$title = mkhyphen($title);

// Assumption made here....
//
// need to get the parent category of this entry.  
// We'll take the first row returned for the itemid:

$sql = "SELECT catid FROM ".$OPREFIX."_cat_items_relations WHERE itemid = '".$itemid."' ORDER BY ordering LIMIT 1";
$new_result=mysql_query($sql,$connection);
$newrow = mysql_fetch_array($new_result);  
$catid = $newrow['catid']; 

// category switching - this is pulled out of the matrix when 
// the categories were pulled over:
//
$parent = ($catid != null)? $cat[$catid] : null;

if (!$parent) { $parent = "was ".$catid;
	print "error: Wasn't able to identify the parent.../n";
}

// put all the data into the object table:
//

$sql = "INSERT INTO `".$NPREFIX."_object` (`nid`, `name`, `approved`, `confirmed`, `counter`, `cout`, `coutTime`, `createdTime`, `defURL`, `metaDesc`, `metaKeys`, `metaAuthor`, `metaRobots`, `options`, `oType`, `owner`, `ownerIP`, `params`, `parent`, `state`, `stateExpl`, `updatedTime`, `updater`, `updaterIP`, `validSince`, `validUntil`, `version`) VALUES ('".$title."', '', 1, 0, 1, 0, '0000-00-00 00:00:00', '".$publish_up."', '', '', '', '', '', '', 'entry', '".$owner."', '".$ip."', '', '".$parent."', 1, '', '".$last_update."', '".$updating_user."', '".$updating_ip."', '".$publish_up."', '0000-00-00 00:00:00', 1)";

if ($insert) {

	$insobjs = dosql($sql);
	$total_entries = count($insobjs);
	$total_entries++;
	$newentryid = mysql_insert_id();

	// This will make a OLD->NEW association to use for all fields
	// instead of just adding digits to the old number.
	// - this allows you to run the script multiple times and
	// maintains the auto_increment functionality of SobiPro tables
	//
	$entryx[$itemid] = $newentryid;

}


// For each SobiPro section we need a 'name' row in the field_data table.
// This field is created per-section above and is ".$section['namefield']." at 
// all times:

$newbaseData = mysql_real_escape_string($baseData);

$sql = "INSERT INTO `".$NPREFIX."_field_data` (`publishUp`, `publishDown`, `fid`, `sid`, `section`, `lang`, `enabled`, `params`, `options`, `baseData`, `approved`, `confirmed`, `createdTime`, `createdBy`, `createdIP`, `updatedTime`, `updatedBy`, `updatedIP`, `copy`) VALUES ('".$publish_up."', '0000-00-00 00:00:00', '".$section['namefield']."', '".$newentryid."', '".$section['id']."', '".$lang."', 1, NULL, NULL, '".$newbaseData."', 1, 0, '".$publish_up."', '".$owner."', '".$ip."', '".$last_update."', '".$updating_user."', '".$updating_ip."', 0)"; 

if ($insert) { $fieldres = dosql($sql); }

// For each entry we need a name row in the language table:
//
//
$sql = "INSERT INTO `".$NPREFIX."_language` (`sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`) VALUES ('name', '', NULL, '".$lang."', 'entry', '0', '".$newentryid."')";  

if ($insert) { $inslang = dosql($sql); }

// here we need to do a row (1) into _relations for the field

if(isset($entryx[$itemid])) {
	$category = ($cat != null) ? $cat : null;
	$sql = "INSERT INTO `".$NPREFIX."_relations` (`id`, `pid`, `oType`, `position`, `validSince`, `validUntil`, `copy`) VALUES ('".$entryx[$itemid]."', '".$category."', 'entry', 1, '".$publish_up."',  '0000-00-00 00:00:00', 0)";
	$rel_result = dosql($sql);
}
else {
	echo  "ERROROOOO";
	exit;
}

$rowcnt++;
	if ($verbose) {
		if ($rowcnt == $qtrdone) { print "25%...."; }
		if ($rowcnt == $halfdone) { print "50%...."; }
		if ($rowcnt == $thirddone) { print "75%...."; }
		}


}

if ($verbose) { print "Done!/n/n"; }

//OK until here .... omg fiouuh :o 

// query the original _fields_data table and populate the new _field_data
// This could be the largest import of data and could take some time...

$sql = "SELECT ".$OPREFIX."_item.publish_up, ".$OPREFIX."_item.publish_down, ".$OPREFIX."_item.published, ".$OPREFIX."_item.title, ".$OPREFIX."_item.confirm, ".$OPREFIX."_fields_data.fieldid, ".$OPREFIX."_item.itemid, ".$OPREFIX."_fields_data.data_txt, ".$OPREFIX."_item.approved, ".$OPREFIX."_item.owner, ".$OPREFIX."_item.ip, ".$OPREFIX."_item.last_update, ".$OPREFIX."_item.updating_user, ".$OPREFIX."_item.updating_ip FROM ".$OPREFIX."_fields_data, ".$OPREFIX."_item WHERE ".$OPREFIX."_item.itemid = ".$OPREFIX."_fields_data.itemid"; 

$getfields = dosql($sql);

$sql_num=mysql_num_rows($getfields);

	if ($verbose) {
	print "Status: Found $sql_num items in original _fields_data table.../n";
	print "Status: Progress: ";
	}

$rowcnt=0;
$qtrdone = round($sql_num/4);
$halfdone = round($sql_num/2);
$thirddone = round($qtrdone*3);

// and loop
//
while($row=mysql_fetch_array($getfields))
{

$itemid = $row['itemid'];
$fieldid = $row['fieldid'];
$title = $row['title'];
$publish_up = $row['publish_up'];
$publish_down = $row['publish_down'];
$published = $row['published'];
$datatxt = $row['data_txt'];
$confirm = $row['confirm'];
$approved = $row['approved'];
$owner = $row['owner'];
$ip = $row['ip'];
$last_update = $row['last_update'];
$updating_user = $row['updating_user'];
$updating_ip = $row['updating_ip'];

// munge up the field xlate here
// ie: insert the data with the newly assigned ID
$newfid=0;
if(isset($fieldx[$fieldid])) {
	$newfid = $fieldx[$fieldid];
}

	if (!$newfid) { 
		$newfid = $fieldid;
 		print "error: failed to convert field id $fieldid at $sid | $datatxt row /n";
	 }

// Assumption: You may not want this done to your titles.
// I found numerous people entered leading spaces, all caps, etc.
//
// Clean up funny titles:
$title = strtotitle($title);
$title = mysql_real_escape_string($title);

// Get rid of crazy all caps entries...
$datatxt = strtolower($datatxt);
$datatxt = strtotitle($datatxt);
$datatxt = mysql_real_escape_string($datatxt);

// $sid becomes this item's entry number from the matrix 
$sid = $entryx[$itemid];

//
// We're going to encounter a number of enries in this table that now 
// belong in field__option_selected and we can't insert them into this 
// table because of a primary key / unique field, but we want one
// entry to indicate there is a field there. (I think)
//
// this needs to somehow detect dupe / conflicting fields where the 
// multi-optional fields come into play
// ....and it works well...

    $val = $rowinserted[$newfid][$sid];
    $skiprow=0;
    // does $rowinserted[$newfid][$sid] have a value?
    if ($val) {
        $skiprow++;
    } else {
        $rowinserted[$newfid][$sid]++;
    }


    if (($skiprow) && ($verbose > 2)) { print "SKIPPING ROW [$newfid][$sid] value = $datatxt/n"; }



// insert each row into the new table:
    $sql = "INSERT INTO ".$NPREFIX."_field_data (`publishUp`, `publishDown`, `fid`, `sid`, `section`, `lang`, `enabled`, `baseData`, `approved`, `confirmed`, `createdTime`, `createdBy`, `createdIP`, `updatedTime`, `updatedBy`, `updatedIP`, `copy`) VALUES ('$publish_up', '$publish_down', '$newfid', '$sid', '$section[id]', '$lang', '$published', '$datatxt', '$approved', '$confirm', '$publish_up', '$owner', '$ip', '$last_update', '$updating_user', '$updating_ip', '0')";


    if (!$skiprow) {
        if ($insert) { $fieldins = dosql($sql); }
    }
    // end the if !$skiprow

    $rowcnt++;

    if ($verbose) {
        if ($rowcnt == $qtrdone) { print "25%...."; }
        if ($rowcnt == $halfdone) { print "50%...."; }
        if ($rowcnt == $thirddone) { print "75%...."; }
    }



}

// END WHILE



if ($verbose) { print "Done!/n/n"; }
exit;
// In my install, fieldtype > 2 appears to be a multiple selection 
// field.  Unverified.
//
//$sql = "SELECT itemid,fieldid,data_txt FROM ".$OPREFIX."_fields_data WHERE fieldid > '2'";
$sql = "SELECT ".$OPREFIX."_fields.fieldid, itemid, data_txt, ".$OPREFIX."_fields.fieldType FROM ".$OPREFIX."_fields_data, ".$OPREFIX."_fields WHERE ".$OPREFIX."_fields_data.fieldid=".$OPREFIX."_fields.fieldid AND ".$OPREFIX."_fields.fieldType > '2'";

$getmults = dosql($sql);
$sql_num=mysql_num_rows($getmults);

	if ($verbose) { print "Status: Found $sql_num options selected in old _fields_data table, moving to _fields_option_selected../n";
	}


while($row=mysql_fetch_array($getmults)) {

$itemid = $row['itemid'];
$fieldid = $row['fieldid'];
$datatxt = $row['data_txt'];

$fid = $fieldx[$fieldid];
$sid = $entryx[$itemid];


$sql = "INSERT INTO `".$NPREFIX."_field_option_selected` (`fid`, `sid`, `optValue`, `params`, `copy`) VALUES ('".$fid."', '".$sid."', '".$datatxt."', '', 0)";

// If for some reason we run into a field we have not mapped we need to skip 
// it here otherwise we'll enter multiple blank rows and error.  If you 
// saw this error, you need to align the mismatched fields earlier in 
// the script.

if ((!$fid) || (!$sid)) { 

	print "error: Skipping fieldid $fieldid, itemid $itemid. (Couldn't translate one of the two)/n"; 

	} else {

	$optres = dosql($sql);

	}


}

if ($verbose) {
print "Final: If everything went right you probably have exactly $total_entries entries in this section of SobiPro now.../n";
}


//// some functions ////

function strtotitle($title) 

// Converts $title to Title Case, and returns the result. 
// I found there here:  http://blogs.sitepoint.com/title-case-in-php/
// Cool stuff! :-)

{ 

// Our array of 'small words' which shouldn't be capitalised if 
// they aren't the first word. Add your own words to taste. 

$smallwordsarray = array( 'of','a','the','and','an','or','nor','but','is','if','then','else','when', 'at','from','by','on','off','for','in','out','over','to','into','with' ); 

// Split the string into separate words 
$words = explode(' ', $title); 
foreach ($words as $key => $word) 
  { // If this word is the first, or it's not one of our small words, capitalise it 
    // with ucwords(). 
    if ($key == 0 or !in_array($word, $smallwordsarray)) $words[$key] = ucwords($word); 
  } 

// Join the words back into a string 

$newtitle = implode(' ', $words); 
return $newtitle; 

}


function dosql($sql) {

	global $debugsql, $host, $user, $password, $database, $connection;

	if ($debugsql) { print "$sql/n"; } 

	$sql_result=mysql_query($sql,$connection)
       	 or exit("Sql Error ".mysql_errno().": ". mysql_error() . "/n");

	return $sql_result;
}

function mkhyphen($line) {

// convert to underscore without non-alpha characters 

$line = preg_replace('/[^(\x20-\x7F)]*/','', $line);
$line = strtolower($line);
$line = str_replace(" ","_",$line);
$line = str_replace("\/","",$line);
$line = str_replace(".","",$line);
$line = str_replace(",","",$line);
$line = str_replace("(","",$line);
$line = str_replace(")","",$line);
$line = str_replace("'","",$line);

return $line;

}


?>
