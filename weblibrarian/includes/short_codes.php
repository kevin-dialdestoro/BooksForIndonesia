<?php

	/* Short codes (front end) */

class WEBLIB_ShortCodes {

  private $SearchTypes;

  function __construct() {

    $this->SearchTypes = array ('title' => __('Title','web-librarian'),
				'author' => __('Author','web-librarian'),
				'subject' => __('Subject','web-librarian') , 
				'keyword' => __('Location','web-librarian'), 
				'isbn' => __('ISBN','web-librarian'));
    $this->CategoryTypes = array ('0' => __('All','web-librarian'),
    '1' => __('Childrens books','web-librarian'),
    '2' => __('Novel and light reading','web-librarian'),
    '3' => __('Textbooks','web-librarian'));
    add_shortcode('weblib_searchform' ,array($this,'search_form'));
    add_shortcode('weblib_itemlist'  ,array($this,'item_list'));
    add_shortcode('weblib_itemdetail',array($this,'item_detail'));

  }

  function search_form ($atts, $content=null, $code="") {
    extract( shortcode_atts ( array(
			'name' => 'searchform',
			'actionurl' => '',
			'method' => 'GET' ), $atts ) );
    $searchby  = isset($_REQUEST['searchby'])  ? $_REQUEST['searchby']  : 'title';
    $inCategory  = isset($_REQUEST['inCategory'])  ? $_REQUEST['inCategory']  : '0';
    $searchbox = isset($_REQUEST['searchbox']) ? $_REQUEST['searchbox'] : '';
    $weblib_orderby = isset( $_REQUEST['weblib_orderby'] ) ? $_REQUEST['weblib_orderby'] : 'title';
    if ( empty( $weblib_orderby ) ) $weblib_orderby = 'title';
    $weblib_order = isset( $_REQUEST['weblib_order'] ) ? $_REQUEST['weblib_order'] : 'ASC';
    if ( empty( $weblib_order ) ) $weblib_order = 'ASC';

    $result  = '<form id="'.$name.'" method="'.$method.'" action="'.
		$actionurl.'">';
        //hack
    $result .= '<p><label for="inCategory">'.'Category '.'</label>';
    $result .= '<select id="inCategory" name="inCategory" >';
    foreach ($this->CategoryTypes as $value => $label) {
      $result .= '<option value="'.$value.'"';
      if ($value == $inCategory) {$result .= ' selected="selected"';}
      $result .= '>'.$label."</option>\n";
    }
    $result .= "</select>\n<br />";
    
    $result .= '<label for="searchby">'. __('Search ','web-librarian').'</label>';
    $result .= '<select id="searchby" name="searchby" >';
    foreach ($this->SearchTypes as $value => $label) {
      $result .= '<option value="'.$value.'"';
      if ($value == $searchby) {$result .= ' selected="selected"';}
      $result .= '>'.$label."</option>\n";
    }
    $result .= "</select>\n";
    
    $result .= '<label for="searchbox">&nbsp;'.__('for','web-librarian').'&nbsp;</label><input id="searchbox" name="searchbox" value="'.$searchbox.'" /><br />';
    
    $result .= '<label for="weblib_orderby">'.__('Sort by ','web-librarian').'</label>';
    $result .= '<select id="weblib_orderby" name="weblib_orderby">';
    foreach (array('barcode' => __('Date added','web-librarian'), 
		   'title' => __('Title','web-librarian'), 
		   'author' => __('Author','web-librarian')) as $field => $label) {
      $result .= '<option value="'.$field.'"';
      if ($field == $weblib_orderby) {$result .= ' selected="selected"';}
      $result .= '>'.$label."</option>\n";
    }
    $result .= "</select>\n";
    $result .= '<select id="weblib_order" name="weblib_order">';
    foreach (array('ASC' => __('Ascending','web-librarian'), 
		   'DESC' => __('Descending','web-librarian')) as
		$value => $label) {
      $result .= '<option value="'.$value.'"';
      if ($value == $weblib_order) {$result .= ' selected="selected"';}
      $result .= '>'.$label."</option>\n";
    }
    $result .= "</select>\n";
    $result .= '<br /><input class="ui-button" type="submit" value="'.__('Search','web-librarian').'" /></p>';
    $result .= "</form>\n";
    return $result;
  }

  function item_list ($atts, $content=null, $code="") {
    extract( shortcode_atts ( array(
	'name' => 'itemlist',
	'per_page' => 10,
	'moreinfourl' => '',
	'inlinemoreinfo' => false,
	'holdbutton' => false ), $atts ) );
    $result = '';

    $result = "\n<!-- barcodetable: _REQUEST is ".print_r($_REQUEST,true)." -->\n";

    $result .= "<!-- barcodetable: holdbutton passed as $holdbutton -->\n";
    if (is_user_logged_in()) {
      $user = wp_get_current_user();
      $patronid = get_user_meta($user->ID,'PatronID',true);
      if ($patronid == '') {
        $holdbutton = false;
      }
    } else {
      $holdbutton = false;
    }
    $result .= "<!-- barcodetable: holdbutton reduced to $holdbutton -->\n";

    if ($inlinemoreinfo) {
      $moreinfourl = get_permalink();
      if (isset($_REQUEST['barcode'])) {
	return $result.$this->item_detail(array('barcode' => $_REQUEST['barcode'], 
					        'holdbutton' => $holdbutton,
						'detaillevel' => 'long'));
      }
    }

    $searchby  = isset($_REQUEST['searchby'])  ? $_REQUEST['searchby']  : 'title';
    $inCategory  = isset($_REQUEST['inCategory'])  ? $_REQUEST['inCategory']  : '0';
    $searchbox = isset($_REQUEST['searchbox']) ? $_REQUEST['searchbox'] : '';
    $weblib_orderby = isset( $_REQUEST['weblib_orderby'] ) ? $_REQUEST['weblib_orderby'] : 'title';
    if ( empty( $weblib_orderby ) ) $weblib_orderby = 'title';
    $weblib_order = isset( $_REQUEST['weblib_order'] ) ? $_REQUEST['weblib_order'] : 'ASC';
    if ( empty( $weblib_order ) ) $weblib_order = 'ASC';

    if ($searchbox == '' && $inCategory == '0') {
      $all_items = WEBLIB_ItemInCollection::AllBarCodes($weblib_orderby,$weblib_order);
    } else if ($searchbox != '' && $inCategory == '0'){
      switch($searchby) {
	case 'title':
	  $all_items = WEBLIB_ItemInCollection::FindItemByTitle('%'.$searchbox.'%',$weblib_orderby,$weblib_order);
	  break;
	case 'author':
	  $all_items = WEBLIB_ItemInCollection::FindItemByAuthor('%'.$searchbox.'%',$weblib_orderby,$weblib_order);
	  break;
	case 'subject':
	  $all_items = WEBLIB_ItemInCollection::FindItemBySubject('%'.$searchbox.'%',$weblib_orderby,$weblib_order);
	  break;
	case 'isbn':
	  $all_items = WEBLIB_ItemInCollection::FindItemByISBN('%'.$searchbox.'%',$weblib_orderby,$weblib_order);
	  break;
	case 'keyword':
	  $all_items = WEBLIB_ItemInCollection::FindItemByKeyword('%'.$searchbox.'%',$weblib_orderby,$weblib_order);
	  break;
      }
    } else if ($searchbox != '' && $inCategory != '0'){
      switch($searchby) {
    case 'title':
      $all_items = WEBLIB_ItemInCollection::FindItemByTitleCategory('%'.$searchbox.'%',$weblib_orderby,$weblib_order,'%'.$inCategory.'%');
      break;
    case 'author':
      $all_items = WEBLIB_ItemInCollection::FindItemByAuthorCategory('%'.$searchbox.'%',$weblib_orderby,$weblib_order,'%'.$inCategory.'%');
      break;
    case 'subject':
      $all_items = WEBLIB_ItemInCollection::FindItemBySubjectCategory('%'.$searchbox.'%',$weblib_orderby,$weblib_order,'%'.$inCategory.'%');
      break;
    case 'isbn':
      $all_items = WEBLIB_ItemInCollection::FindItemByISBNCategory('%'.$searchbox.'%',$weblib_orderby,$weblib_order,'%'.$inCategory.'%');
      break;
    case 'keyword':
      $all_items = WEBLIB_ItemInCollection::FindItemByKeywordCategory('%'.$searchbox.'%',$weblib_orderby,$weblib_order,'%'.$inCategory.'%');
      break;
      }
    } else {
      $all_items = WEBLIB_ItemInCollection::FindItemByCategory('%'.$searchbox.'%',$weblib_orderby,$weblib_order,'%'.$inCategory.'%');
    }

    $per_page = isset($_REQUEST['per_page']) ? $_REQUEST['per_page'] : $per_page;
    if ($per_page < 1) $per_page = 1;

    $total_items = count($all_items);
    if ($total_items == 1 && $inlinemoreinfo) {
      return $result.$this->item_detail(array('barcode' => $all_items[0],
					      'holdbutton' => $holdbutton,
					      'detaillevel' => 'long'));
    }

    $result .= '<span class="weblib-total-results">';
    if ($total_items==1) {
	$result .= __('1 Book found','web-librarian');
    } else {
	$result .= sprintf(__('%d Books Collected..','web-librarian'),$total_items);
    }
    $result .= '</span><br clear="all" />';

    $total_pages = ceil( $total_items / $per_page );
    $pagenum = isset($_REQUEST['pagenum']) ? $_REQUEST['pagenum'] : 1;
    if ($pagenum < 1) {
      $pagenum = 1;
    } else if ($pagenum > $total_pages && $total_pages > 0) {
      $pagenum = $total_pages;
    }
    $start = ($pagenum-1)*$per_page;
    $items = array_slice( $all_items,$start,$per_page );

    if ($moreinfourl != '') {
      $moreinfourl = add_query_arg(array('searchby' => $searchby,
					 'searchbox' => $searchbox,
					 'weblib_orderby' => $weblib_orderby,
					 'weblib_order' => $weblib_order,
                     'inCategory' => $inCategory),$moreinfourl);
    }

    $result .= $this->generate_pagination($pagenum,$total_pages,$per_page,
					  array('searchby' => $searchby,
						'searchbox' => $searchbox,
						'weblib_orderby' => $weblib_orderby,
						'weblib_order' => $weblib_order,
                        'inCategory' => $inCategory));
    $result .= '<div class="weblib-item-table">';
    $index = $start;
    foreach ($items as $barcode) {
      $result .= '<div class="weblib-item-row">';
      $result .= '<span class="weblib-item-index weblib-item-element">'.++$index.'.</span>';
      $result .= $this->item_detail(array('barcode' => $barcode,
      					  'getbarcode' => false,
					  'holdbutton' => $holdbutton,
					  'moreinfourl' => $moreinfourl));
      $result .= '</div>';
    }
    $result .= '</div>';
    

    $result .= $this->generate_pagination($pagenum,$total_pages,$per_page,
					  array('searchby' => $searchby,
						'searchbox' => $searchbox,
						'weblib_orderby' => $weblib_orderby,
						'weblib_order' => $weblib_order,
						'inCategory' => $inCategory));
    return $result;
  }

  function generate_pagination($pagenum,$lastpage,$per_page,$otherparams) {

    //file_put_contents("php://stderr","*** WEBLIB_ShortCodes::generate_pagination($pagenum,$lastpage,$per_page,".print_r($otherparams,true).")\n");

    if ($lastpage == 1) {
      return '';
    }

    $result  = '<div class="weblib-item-pagination-table">';
    $result .= '<div class="weblib-item-pagination">';
    $result .= '<div class="pagelink"><a class="ui-button" href="';
    $result .= add_query_arg(array_merge($otherparams,
					 array('pagenum' => 1,
					       'per_page' => $per_page)),
			     get_permalink( ));
    $result .= '">&lt;&lt;</a></div>';
    $result .= '<div class="pagelink"><a class="ui-button" href="';
    $result .= add_query_arg(array_merge($otherparams,
					 array('pagenum' => 
						  $pagenum > 1 ? $pagenum-1 : 1,
					       'per_page' => $per_page)),
			     get_permalink( ));
    $result .= '">&lt;</a></div>';
    $result .= '<div style = "white-space:nowrap;" class="pagelink pagenumform">';
    $result .= '<form action="'.get_permalink( ).'" method="get">';
    $result .= '<input class="ui-button" type="submit" value="'. __('Page','web-librarian').'" />';
    $result .= '<input id = "pagenum" name="pagenum" type="text" size="2" maxlength="2" value="'.$pagenum.'" />';
    $result .= '<label for "pagenum">'.'of'.'&nbsp;'.$lastpage.'</label>';
    foreach (array_merge($otherparams,array('per_page' => $per_page)) as $key => $val) {
      $result .= '<input type="hidden" name="'.$key.'" value="'.$val.'" />';
    }
    $result .= '</form></div>';
    $result .= '<div class="pagelink"><a class="ui-button" href="';
    $result .= add_query_arg(array_merge($otherparams,
					 array('pagenum' =>
						$pagenum < $lastpage ? $pagenum+1 : $lastpage,
						'per_page' => $per_page)),
			     get_permalink( ));
    $result .= '">&gt;</a></div>';
    $result .= '<div class="pagelink"><a class="ui-button" href="';
    $result .= add_query_arg(array_merge($otherparams,
			     		array('pagenum' => $lastpage,
				   	'per_page' => $per_page)),
			     get_permalink( ));
    $result .= '">&gt;&gt;</a></div>';
    $result .= '</div></div><br clear="all" />';
    return $result;
  }

  function item_detail ($atts, $content=null, $code="") {
    extract( shortcode_atts ( array(
      'name' => 'itemdetail[%i]',
      'barcode' => '',
      'getbarcode' => true,
      'holdbutton' => false,
      'detaillevel' => 'brief',
      'moreinfourl' => '' ), $atts ) );
    $result = '';

    if ($getbarcode) {
      $barcode = isset($_REQUEST['barcode']) ? $_REQUEST['barcode'] : $barcode;
    }

    if (!WEBLIB_ItemInCollection::IsItemInCollection($barcode)) {
      return '<p><span id="error">'.sprintf(__('No such item: %s','web-librarian'),$barcode).'</span></p>';
    } else {
      $item = new WEBLIB_ItemInCollection($barcode);
    }

    switch ($detaillevel) {
      case 'long':
	$result .= '<div class="weblib-item-long">';
	$result .= '<div class="weblib-item-head weblib-item-row">';
	$result .= '<div class="weblib-item-left weblib-item-element">';
        $result .= '<div class="weblib-item-content-block">';
	$result .= '<span class="weblib-item-content-element">';
	$result .= '<span class="weblib-item-left-head">'.__('Author','web-librarian').'</span>';
	$result .= '<span class="weblib-item-left-content weblib-item-author">'.$item->author().'</span>';
	$result .= '</span><!-- weblib-item-content-element -->';
	$result .= '<span class="weblib-item-content-element">';
	$result .= '<span class="weblib-item-left-head">'.__('Title','web-librarian').'</span>';
	$result .= '<span class="weblib-item-left-content weblib-item-title">'.$item->title().'</span>';
	$result .= '</span><!-- weblib-item-content-element -->';
	$result .= '<span class="weblib-item-content-element">';
	$result .= '<span class="weblib-item-left-head">'.__('Published','web-librarian').'</span>';
	$result .= '<span class="weblib-item-left-content">';
	$publoc  = $item->publocation();
	$pub     = $item->publisher();
	$pubdate = $item->pubdate();
	if ($publoc != '') {
	  $result .= $publoc.'&nbsp:&nbsp;';
	}
	$result .= $pub;
	if ($pubdate != '') {
	  $result .= ' '.mysql2date('Y',$pubdate);
	}
	$result .= '</span><!-- weblib-item-left-content -->';	
	$result .= '</span><!-- weblib-item-content-element -->';
	$result .= '</div><!-- weblib-item-content-block -->';	
        $result .= '</div><!-- weblib-item-left -->';
	$result .= '<div class="weblib-item-right weblib-item-element">';
	$result .= '<span class="weblib-item-thumb">';
	if ($item->thumburl() != '') {
	  $result .= '<img src="'.$item->thumburl().'" border="0"  />';
	} else {
	  $result .= '<img src="'.WEBLIB_IMAGEURL.'/nothumb.png" border="0" width="48" height="72" />';
	}
        $result .= '</span><!-- weblib-item-thumb -->';
	if ($holdbutton) {
	  $result .= '<br /><span class="weblib-item-holdbutton">';
	  $result .= '<input class="weblib-button" type="button" value="'.__('Request','web-librarian').'" onClick="PlaceHold('."'".$barcode."');".'" />';
	  $result .= '</span><!-- weblib-item-holdbutton -->';
	}
	$result .= '</div><!-- weblib-item-right" -->';
	$result .= '</div><!-- weblib-item-head -->';
	$result .= '<div class="weblib-item-body weblib-item-row">';
	$result .= '<div class="weblib-item-left weblib-item-element">';
	$result .= '<div class="weblib-item-content-block">';
	// $result .= '<span class="weblib-item-content-element">';
	// $result .= '<span class="weblib-item-left-head">'.__('Status:','web-librarian').'</span>';
	// $result .= '<span class="weblib-item-left-content">';
	// $outitem = WEBLIB_OutItem::OutItemByBarcode($barcode);
	// $numberofholds = WEBLIB_HoldItem::HoldCountsOfBarcode($barcode);
	// if ($outitem != null) {
	  // $result .= __('Due ','web-librarian');
	  // $duedate = $outitem->datedue();
	  // if (mysql2date('U',$duedate) < time()) {
	    // $result .= '<span class="overdue" >'.strftime('%x',mysql2date('U',$duedate)).'</span>';
	  // } else {
	    // $result .= strftime('%x',mysql2date('U',$duedate));
	  // }
	// } else {
	  // $result .= __('Check Shelves','web-librarian');
	// }
	// $result .= '&nbsp;<span id="hold-count-'.$barcode.'">';
	// if ($numberofholds > 0) {
          // $result .= sprintf(_n('%d Hold','%d Holds',$numberofholds,'web-librarian'),
                             // $numberofholds);
	// }
	// $result .= '</span><!-- hold-count-... -->';
	// $result .= '</span><!-- weblib-item-left-content -->';
	// $result .= '</span><!-- weblib-item-content-element -->';
	if ($item->subject() != '') {
	  $result .= '<span class="weblib-item-content-element">';
	  $result .= '<span class="weblib-item-left-head">'.__('Subject','web-librarian').'</span>';
	  $result .= '<span class="weblib-item-left-content">'.$item->subject().'</span>';
	  $result .= '</span><!-- weblib-item-content-element -->';
	}
	if ($item->category() != '') {
	  $result .= '<span class="weblib-item-content-element">';
	  $result .= '<span class="weblib-item-left-head">'.__('Category','web-librarian').'</span>';
	  $result .= '<span class="weblib-item-left-content">'.$item->category().'</span>';
	  $result .= '</span><!-- weblib-item-content-element -->';
	}
	if ($item->media() != '') {
	  $result .= '<span class="weblib-item-content-element">';
	  $result .= '<span class="weblib-item-left-head">'.__('Media','web-librarian').'</span>';
	  $result .= '<span class="weblib-item-left-content">'.$item->media().'</span>';
	  $result .= '</span><!-- weblib-item-content-element -->';
	}
	if ($item->edition() != '') {
	  $result .= '<span class="weblib-item-content-element">';
	  $result .= '<span class="weblib-item-left-head">'.__('Edition','web-librarian').'</span>';
	  $result .= '<span class="weblib-item-left-content">'.$item->edition().'</span>';
	  $result .= '</span><!-- weblib-item-content-element -->';
	}
	if ($item->isbn() != '') {
	  $result .= '<span class="weblib-item-content-element">';
	  $result .= '<span class="weblib-item-left-head">'.__('ISBN','web-librarian').'</span>';
	  $result .= '<span class="weblib-item-left-content">'.$item->isbn().'</span>';
	  $result .= '</span><!-- weblib-item-content-element -->';
	}
	if ($item->callnumber() != '') {
	  $result .= '<span class="weblib-item-content-element">';
	  $result .= '<span class="weblib-item-left-head">'.__('Call Number','web-librarian').'</span>';
	  $result .= '<span class="weblib-item-left-content">'.$item->callnumber().'</span>';
	  $result .= '</span><!-- weblib-item-content-element -->';
	}
	// if ($item->type() != '') {
	  // $result .= '<span class="weblib-item-content-element">';
	  // $result .= '<span class="weblib-item-left-head">'.__('Type','web-librarian').'</span>';
	  // $result .= '<span class="weblib-item-left-content">'.$item->type().'</span>';
	  // $result .= '</span><!-- weblib-item-content-element -->';
	// }
	if ($item->description() != '') {
	  $result .= '<span class="weblib-item-content-element">';
	  $result .= '<span class="weblib-item-left-head">Description</span>';
	  $result .= '<div class="weblib-item-left-content">'.$item->description().'</div>';
	  $result .= '</span><!-- weblib-item-content-element -->';
	}
	$result .= '</div><!-- weblib-item-content-block -->';
	$result .= '</div><!-- weblib-item-left -->';
	$result .= '<div class="weblib-item-right weblib-item-element">';
    #HACK
	$result .= '<span class="weblib-item-center-head">'.__('Location','web-librarian').'</span>';
	$result .= '<ul>';
	$space  = '';
	foreach ($item->keywordsof() as $keyword) {
	  $result .= '<li>'.$keyword.'</li>';
	}
	$result .= '</ul>';
	$result .= '</div><!-- weblib-item-right" -->';
	$result .= '</div><!-- weblib-item-body -->';
	$result .= '</div><!-- weblib-item-long -->';
	break;
      case 'brief':
      default:
	$result .= '<span class="weblib-item-brief weblib-item-thumb weblib-item-element">';
	if ($item->thumburl() != '') {
	  $result .= '<img src="'.$item->thumburl().'" border="0" />';
	} else {
	  $result .= '<img src="'.WEBLIB_IMAGEURL.'/nothumb.png" border="0" width="48" height="72" />';
	}
        $result .= '</span>';
	$result .= '<span class="weblib-item-brief weblib-item-info weblib-item-element">';
	if ($moreinfourl != '') {
	  $result .= '<a href="'.add_query_arg(array('barcode' => $barcode),
						$moreinfourl).'">';
	}
	$result .= $item->title();
	if ($moreinfourl != '') {$result .= '</a>';}
	$result .= '<br />';
	$result .= $item->author();
    
    #HACK
    $result .= '<br />';
    $space  = '';
    foreach ($item->keywordsof() as $keyword) {
      $result .= $space.$keyword;
      $space  = '; ';
    }
    
	if ($item->callnumber() != '') {
	  $result .= '<br />'.__('Call Number:','web-librarian').'&nbsp;'.$item->callnumber();
	}
	$outitem = WEBLIB_OutItem::OutItemByBarcode($barcode);
	$numberofholds = WEBLIB_HoldItem::HoldCountsOfBarcode($barcode);
	if ($outitem != null) {
	  $result .= '<br />'.__('Due ','web-librarian');
	  $duedate = $outitem->datedue();
	  if (mysql2date('U',$duedate) < time()) {
	    $result .= '<span class="overdue" >'.strftime('%x',mysql2date('U',$duedate)).'</span>';
	  } else {
	    $result .= strftime('%x',mysql2date('U',$duedate));
	  }
	  
	}
	$result .= '<br /><span id="hold-count-'.$barcode.'">';
	if ($numberofholds > 0) {
          $result .= sprintf(_n('%d Hold','%d Holds',$numberofholds,'web-librarian'),
                             $numberofholds);
	}
	$result .= '</span></span>';
	if ($holdbutton) {
	  $result .= '<span class="weblib-item-holdbutton weblib-item-element">';
	  $result .= '<input class="weblib-button" type="button" value="'.__('Request','web-librarian').'" onClick="PlaceHold('."'".$barcode."');".'" />';
	  $result .= '</span>';
	}
	break;
    }
    return $result;
  }


}

?>
