<?php
/**********************************************************************
  Page for searching item list and select it to item selection
  in sales order and purchase order.
***********************************************************************/
$page_security = "SA_ITEM";
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

$js = get_js_select_combo_item();
$js .= get_js_populate_combo_item();

page(_($help_context = "Items"), @$_REQUEST["popup"], false, "", $js);

// Activate Ajax on form submit
if(get_post("search")) {
	$Ajax->activate("item_tbl");
}

// BEGIN: Filter form. Use query string so the client_id will not disappear
// after ajax form post.
start_form(false, false, $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);

start_table(TABLESTYLE_NOBORDER);

start_row();

text_cells(_("Description"), "description");
submit_cells("search", _("Search"), "", _("Search items"), "default");

end_row();

end_table();

end_form();
// END: Filter form

// BEGIN: Link to add new item
// hyperlink_params($path_to_root . "/inventory/manage/items.php", _("Add new"), "popup=1");
// END: Link to add new item

// BEGIN: Item list
div_start("item_tbl");

start_table(TABLESTYLE);

$th = array("", _("Item Code"), _("Description"), _("Category"));

table_header($th);

// Get the item list type.
$type = "";
if (isset($_GET['type'])) {
	$type = $_GET['type'];
}

// For item that can be sold we should join to item_codes table so we can sell item kits.
if ($type == "sales") {
	// Query based on function sales_items_list in includes/ui/ui_lists.inc.
	$sql = "SELECT DISTINCT i.item_code, i.description, c.description category
		FROM
			".TB_PREF."stock_master s,
			".TB_PREF."item_codes i
		LEFT JOIN
			".TB_PREF."stock_category c
			ON i.category_id=c.category_id
		WHERE i.stock_id=s.stock_id
			AND !i.inactive AND !s.inactive
			AND i.description LIKE " . db_escape("%" . get_post("description"). "%");
}
else {
	// Query based on function stock_items_list( in includes/ui/ui_lists.inc.
	$sql = "SELECT s.stock_id item_code, s.description, c.description category
		FROM ".TB_PREF."stock_master s,".TB_PREF."stock_category c
		WHERE s.category_id=c.category_id
			AND !s.inactive
			AND s.description LIKE " . db_escape("%" . get_post("description"). "%");
}

// The 'all' options have nothing to do with the SQL.
switch ($type) {
	case "sales":
		$sql .= " AND !s.no_sale";
	break;
	case "manufactured":
		$sql .= " AND mb_flag = 'M'";
	break;
	case "costable":
		$sql .= " AND mb_flag != 'D'";
	break;
	case "purchasable":
		// Has nothing to do in item for purchase.
	break;
}

$sql .= " ORDER BY s.description LIMIT 0, 10"; // We only display 10 items.

$result = db_query($sql, "Failed in retreiving item list.");

// Check if we should append selected option to dropdown.
// Usefull when the search item is enabled from company setup.
$append = "false";
if (get_company_pref("no_item_list") === "1") {
	$append = "true";
}

$k = 0; //row colour counter

while ($myrow = db_fetch_assoc($result)) {
	alt_table_row_color($k);
	ahref_cell(_("Select"), 'javascript:void(0)', '', 'selectComboItem(window.opener.document, &quot;' . $_GET["client_id"] . '&quot;, &quot;' . $myrow["item_code"] . '&quot;, &quot;' . $myrow["description"] . '&quot;, ' . $append . ')');
	label_cell($myrow["item_code"]);
	label_cell($myrow["description"]);
	label_cell($myrow["category"]);
	end_row();
}

end_table(1);

div_end();
// END: Item list

end_page();
