<?php
include_once('tmcdecode.php');
include_once('tmclocation.php');
include_once('tmcjson.php');

function nonempty($s)
{
	return($s !== "");
}

function array_desc($a)
{
	if($a['class'] == 'P')
		$data = array($a['junctionnumber'], $a['rnid'], $a['n1id'], $a['n2id']);
	else if($a['class'] == 'L')
		$data = array($a['roadnumber'], $a['rnid'], $a['n1id'], $a['n2id']);
	else
		$data = array($a['nid']);
	return implode(" ", array_filter($data, "nonempty"));
}

function location_link($lcd)
{
	global $cid, $tabcd;

	return "<a href=\"/tmc/tmcview.php?cid=$cid&amp;tabcd=$tabcd&amp;lcd=$lcd\">$cid:$tabcd:$lcd</a>";
}

$units = array('', '', 'm', '%', 'km/h', 'min', '°C', 'min', 't', 'm', 'mm', 'MHz', 'kHz');
$urgencies = array(0 => 'normal', 1 => 'urgent', 2 => 'extremely urgent');

$cid = (array_key_exists('cid', $_REQUEST) ? (int)$_REQUEST['cid'] : 58);
$tabcd = (array_key_exists('tabcd', $_REQUEST) ? (int)$_REQUEST['tabcd'] : 1);

$ecd = (array_key_exists('ecd', $_REQUEST) ? (int)$_REQUEST['ecd'] : 0);
$lcd = (array_key_exists('lcd', $_REQUEST) ? (int)$_REQUEST['lcd'] : 0);
$dir = (array_key_exists('dir', $_REQUEST) ? (int)$_REQUEST['dir'] : 0);
$ext = (array_key_exists('ext', $_REQUEST) ? (int)$_REQUEST['ext'] : 0);
$dur = (array_key_exists('dur', $_REQUEST) ? (int)$_REQUEST['dur'] : null);
$div = (array_key_exists('div', $_REQUEST) ? (int)$_REQUEST['div'] : null);
$bits = (array_key_exists('bits', $_REQUEST) ? $_REQUEST['bits'] : "");

$time = (array_key_exists('time', $_REQUEST) ? (int)$_REQUEST['time'] : time());

ob_start();
$message = decode_message($ecd, $lcd, $dir, $ext, $dur, $div, $bits);
$raw = ob_get_contents();
ob_end_clean();

$roles = array('' => array(), 'entry' => array(), 'exit' => array(), 'ramp' => array(), 'parking' => array(), 'fuel' => array(), 'restaurant' => array());

foreach($message['iblocks'] as $iblock)
{
	foreach($iblock['events'] as $event)
	{
		switch($event['code'])
		{
		case 1: // traffic problem
		case 2: // queuing traffic (with average speeds Q). Danger of stationary traffic
		case 55: // traffic problem expected
		case 56: // traffic congestion expected
		case 70: // traffic congestion, average speed of  10 km/h
		case 71: // traffic congestion, average speed of  20 km/h
		case 72: // traffic congestion, average speed of  30 km/h
		case 73: // traffic congestion, average speed of  40 km/h
		case 74: // traffic congestion, average speed of  50 km/h
		case 75: // traffic congestion, average speed of  60 km/h
		case 76: // traffic congestion, average speed of  70 km/h
		case 80: // heavy traffic has to be expected
		case 81: // traffic congestion has to be expected
		case 84: // major event. Heavy traffic has to be expected
		case 85: // sports meeting. Heavy traffic has to be expected
		case 86: // fair. Heavy traffic has to be expected
		case 87: // evacuation. Heavy traffic has to be expected
		case 91: // delays (Q) for cars
		case 101: // stationary traffic
		case 102: // stationary traffic for 1 km
		case 103: // stationary traffic for 2 km
		case 104: // stationary traffic for 4 km
		case 105: // stationary traffic for 6 km
		case 106: // stationary traffic for 10 km
		case 107: // stationary traffic expected
		case 108: // queuing traffic (with average speeds Q)
		case 109: // queuing traffic for 1 km (with average speeds Q)
		case 110: // queuing traffic for 2 km (with average speeds Q)
		case 111: // queuing traffic for 4 km (with average speeds Q)
		case 112: // queuing traffic for 6 km (with average speeds Q)
		case 113: // queuing traffic for 10 km (with average speeds Q)
		case 114: // queuing traffic expected
		case 115: // slow traffic (with average speeds Q)
		case 116: // slow traffic for 1 km (with average speeds Q)
		case 117: // slow traffic for 2 km (with average speeds Q)
		case 118: // slow traffic for 4 km (with average speeds Q)
		case 119: // slow traffic for 6 km (with average speeds Q)
		case 120: // slow traffic for 10 km (with average speeds Q)
		case 121: // slow traffic expected
		case 122: // heavy traffic (with average speeds Q)
		case 123: // heavy traffic expected
		case 125: // traffic building up (with average speeds Q)
		case 129: // stationary traffic for 3 km
		case 130: // danger of stationary traffic
		case 131: // queuing traffic for 3 km (with average speeds Q)
		case 132: // danger of queuing traffic (with average speeds Q)
		case 133: // long queues (with average speeds Q)
		case 134: // slow traffic for 3 km (with average speeds Q)
		case 136: // traffic congestion (with average speeds Q)
		case 138: // queuing traffic (with average speeds Q). Approach with care
		case 139: // queuing traffic around a bend in the road
		case 140: // queuing traffic over the crest of a hill
		case 142: // traffic heavier than normal (with average speeds Q)
		case 143: // traffic very much heavier than normal (with average speeds Q)
			$roles[''][] = 'traffic';
			break;
		case 11: // overheight warning system triggered
		case 28: // road closed intermittently
		case 41: // (Q) overtaking lane(s) closed
		case 42: // (Q) overtaking lane(s) blocked
		case 51: // roadworks, (Q) overtaking lane(s) closed
		case 52: // (Q sets of) roadworks on the hard shoulder
		case 53: // (Q sets of) roadworks in the emergency lane
		case 82: // (Q sets of) roadworks. Heavy traffic has to be expected
		case 482: // express lanes closed
		case 483: // through traffic lanes closed
		case 484: // local lanes closed
		case 489: // express lanes blocked
		case 490: // through traffic lanes blocked
		case 491: // local lanes blocked
		case 492: // no motor vehicles
		case 493: // restrictions
		case 494: // closed for heavy lorries (over Q)
		case 500: // (Q) lane(s) closed
		case 501: // (Q) right lane(s) closed
		case 502: // (Q) centre lane(s) closed
		case 503: // (Q) left lane(s) closed
		case 504: // hard shoulder closed
		case 505: // two lanes closed
		case 506: // three lanes closed
		case 507: // (Q) right lane(s) blocked
		case 508: // (Q) centre lane(s) blocked
		case 509: // (Q) left lane(s) blocked
		case 510: // hard shoulder blocked
		case 511: // two lanes blocked
		case 512: // three lanes blocked
		case 513: // single alternate line traffic
		case 514: // carriageway reduced (from Q lanes) to one lane
		case 515: // carriageway reduced (from Q lanes) to two lanes
		case 516: // carriageway reduced (from Q lanes) to three lanes
		case 517: // contraflow
		case 518: // narrow lanes
		case 519: // contraflow with narrow lanes
		case 520: // (Q) lane(s) blocked
		case 544: // (Q) lanes closed. Traffic flowing freely
		case 564: // carriageway reduced (from Q lanes) to two lanes. Traffic flowing freely
		case 574: // carriageway reduced (from Q lanes) to three lanes. Traffic flowing freely
		case 599: // contraflow. Traffic flowing freely
		case 612: // narrow lanes. Traffic flowing freely
		case 622: // contraflow with narrow lanes. Traffic flowing freely
		case 637: // emergency lane closed
		case 638: // turning lane closed
		case 639: // crawler lane closed
		case 640: // slow vehicle lane closed
		case 641: // one lane closed
		case 642: // emergency lane blocked
		case 643: // turning lane blocked
		case 644: // crawler lane blocked
		case 645: // slow vehicle lane blocked
		case 646: // one lane blocked
		case 648: // (Q person) carpool lane closed
		case 649: // (Q person) carpool lane blocked
		case 666: // intermittent short term closures
		case 675: // (Q) salting vehicles
		case 676: // bus lane blocked
		case 678: // heavy vehicle lane closed
		case 679: // heavy vehicle lane blocked
		case 681: // (Q) snowploughs
		case 701: // (Q sets of) roadworks
		case 702: // (Q sets of) major roadworks
		case 703: // (Q sets of) maintenance work
		case 704: // (Q sections of) resurfacing work
		case 705: // (Q sets of) central reservation work
		case 706: // (Q sets of) road marking work
		case 707: // bridge maintenance work (at Q bridges)
		case 708: // (Q sets of) temporary traffic lights
		case 709: // (Q sections of) blasting work
		case 733: // (Q sets of) roadworks. Traffic flowing freely
		case 736: // (Q sets of) roadworks. Right lane closed
		case 737: // (Q sets of) roadworks. Centre lane closed
		case 738: // (Q sets of) roadworks. Left lane closed
		case 739: // (Q sets of) roadworks. Hard shoulder closed
		case 740: // (Q sets of) roadworks. Two lanes closed
		case 741: // (Q sets of) roadworks. Three lanes closed
		case 742: // (Q sets of) roadworks. Single alternate line traffic
		case 743: // roadworks. Carriageway reduced (from Q lanes) to one lane
		case 744: // roadworks. Carriageway reduced (from Q lanes) to two lanes
		case 745: // roadworks. Carriageway reduced (from Q lanes) to three lanes
		case 746: // (Q sets of) roadworks. Contraflow
		case 773: // (Q sections of) resurfacing work. Traffic flowing freely
		case 775: // (Q sections of) resurfacing work. Single alternate line traffic
		case 776: // resurfacing work. Carriageway reduced (from Q lanes) to one lane
		case 777: // resurfacing work. Carriageway reduced (from Q lanes) to two lanes
		case 778: // resurfacing work. Carriageway reduced (from Q lanes) to three lanes
		case 779: // (Q sections of) resurfacing work. Contraflow
		case 791: // (Q sets of) road marking work. Traffic flowing freely
		case 793: // (Q sets of) road marking work. Right lane closed
		case 794: // (Q sets of) road marking work. Centre lane closed
		case 795: // (Q sets of) road marking work. Left lane closed
		case 796: // (Q sets of) road marking work. Hard shoulder closed
		case 797: // (Q sets of) road marking work. Two lanes closed
		case 798: // (Q sets of) road marking work. Three lanes closed
		case 802: // (Q sets of) long-term roadworks
		case 803: // (Q sets of) construction work
		case 804: // (Q sets of) slow moving maintenance vehicles
		case 805: // bridge demolition work (at Q bridges)
		case 806: // (Q sets of) water main work
		case 807: // (Q sets of) gas main work
		case 808: // (Q sets of) work on buried cables
		case 809: // (Q sets of) work on buried services
		case 810: // new roadworks layout
		case 811: // new road layout
		case 815: // (Q sets of) roadworks during the day time
		case 816: // (Q sets of) roadworks during off-peak periods
		case 817: // (Q sets of) roadworks during the night
		case 821: // (Q sets of) resurfacing work during the day time
		case 822: // (Q sets of) resurfacing work during off-peak periods
		case 823: // (Q sets of) resurfacing work during the night
		case 824: // (Q sets of) road marking work. Danger
		case 833: // (Q sets of) slow moving maintenance vehicles. Traffic flowing freely
		case 835: // (Q sets of) slow moving maintenance vehicles. Right lane closed
		case 836: // (Q sets of) slow moving maintenance vehicles. Centre lane closed
		case 837: // (Q sets of) slow moving maintenance vehicles. Left lane closed
		case 838: // (Q sets of) slow moving maintenance vehicles. Two lanes closed
		case 839: // (Q sets of) slow moving maintenance vehicles. Three lanes closed
		case 852: // construction traffic merging
		case 853: // roadwork clearance in progress
			$roles[''][] = 'obstruction';
			break;
		case 521: // (Q) lanes closed. Stationary traffic
		case 522: // (Q) lanes closed. Stationary traffic for 1 km
		case 523: // (Q) lanes closed. Stationary traffic for 2 km
		case 524: // (Q) lanes closed. Stationary traffic for 4 km
		case 525: // (Q) lanes closed. Stationary traffic for 6 km
		case 526: // (Q) lanes closed. Stationary traffic for 10 km
		case 527: // (Q) lanes closed. Danger of stationary traffic
		case 528: // (Q) lanes closed. Queuing traffic
		case 529: // (Q) lanes closed. Queuing traffic for 1 km
		case 530: // (Q) lanes closed. Queuing traffic for 2 km
		case 531: // (Q) lanes closed. Queuing traffic for 4 km
		case 532: // (Q) lanes closed. Queuing traffic for 6 km
		case 533: // (Q) lanes closed. Queuing traffic for 10 km
		case 534: // (Q) lanes closed. Danger of queuing traffic
		case 535: // (Q) lanes closed. Slow traffic
		case 536: // (Q) lanes closed. Slow traffic for 1 km
		case 537: // (Q) lanes closed. Slow traffic for 2 km
		case 538: // (Q) lanes closed. Slow traffic for 4 km
		case 539: // (Q) lanes closed. Slow traffic for 6 km
		case 540: // (Q) lanes closed. Slow traffic for 10 km
		case 541: // (Q) lanes closed. Slow traffic expected
		case 542: // (Q) lanes closed. Heavy traffic
		case 543: // (Q) lanes closed. Heavy traffic expected
		case 545: // (Q) lanes closed. Traffic building up
		case 546: // carriageway reduced (from Q lanes) to one lane. Stationary traffic
		case 547: // carriageway reduced (from Q lanes) to one lane. Danger of stationary traffic
		case 548: // carriageway reduced (from Q lanes) to one lane. Queuing traffic
		case 549: // carriageway reduced (from Q lanes) to one lane. Danger of queuing traffic
		case 550: // carriageway reduced (from Q lanes) to one lane. Slow traffic
		case 551: // carriageway reduced (from Q lanes) to one lane. Slow traffic expected
		case 552: // carriageway reduced (from Q lanes) to one lane. Heavy traffic
		case 553: // carriageway reduced (from Q lanes) to one lane. Heavy traffic expected
		case 554: // carriageway reduced (from Q lanes) to one lane. Traffic flowing freely
		case 555: // carriageway reduced (from Q lanes) to one lane. Traffic building up
		case 556: // carriageway reduced (from Q lanes) to two lanes. Stationary traffic
		case 557: // carriageway reduced (from Q lanes) to two lanes. Danger of stationary traffic
		case 558: // carriageway reduced (from Q lanes) to two lanes. Queuing traffic
		case 559: // carriageway reduced (from Q lanes) to two lanes. Danger of queuing traffic
		case 560: // carriageway reduced (from Q lanes) to two lanes. Slow traffic
		case 561: // carriageway reduced (from Q lanes) to two lanes. Slow traffic expected
		case 562: // carriageway reduced (from Q lanes) to two lanes. Heavy traffic
		case 563: // carriageway reduced (from Q lanes) to two lanes. Heavy traffic expected
		case 565: // carriageway reduced (from Q lanes) to two lanes. Traffic building up
		case 566: // carriageway reduced (from Q lanes) to three lanes. Stationary traffic
		case 567: // carriageway reduced (from Q lanes) to three lanes. Danger of stationary traffic
		case 568: // carriageway reduced (from Q lanes) to three lanes. Queuing traffic
		case 569: // carriageway reduced (from Q lanes) to three lanes. Danger of queuing traffic
		case 570: // carriageway reduced (from Q lanes) to three lanes. Slow traffic
		case 571: // carriageway reduced (from Q lanes) to three lanes. Slow traffic expected
		case 572: // carriageway reduced (from Q lanes) to three lanes. Heavy traffic
		case 573: // carriageway reduced (from Q lanes) to three lanes. Heavy traffic expected
		case 575: // carriageway reduced (from Q lanes) to three lanes. Traffic building up
		case 576: // contraflow. Stationary traffic
		case 577: // contraflow. Stationary traffic for 1 km
		case 578: // contraflow. Stationary traffic for 2 km
		case 579: // contraflow. Stationary traffic for 4 km
		case 580: // contraflow. Stationary traffic for 6 km
		case 581: // contraflow. Stationary traffic for 10 km
		case 582: // contraflow. Danger of stationary traffic
		case 583: // contraflow. Queuing traffic
		case 584: // contraflow. Queuing traffic for 1 km
		case 585: // contraflow. Queuing traffic for 2 km
		case 586: // contraflow. Queuing traffic for 4 km
		case 587: // contraflow. Queuing traffic for 6 km
		case 588: // contraflow. Queuing traffic for 10 km
		case 589: // contraflow. Danger of queuing traffic
		case 590: // contraflow. Slow traffic
		case 591: // contraflow. Slow traffic for 1 km
		case 592: // contraflow. Slow traffic for 2 km
		case 593: // contraflow. Slow traffic for 4 km
		case 594: // contraflow. Slow traffic for 6 km
		case 595: // contraflow. Slow traffic for 10 km
		case 596: // contraflow. Slow traffic expected
		case 597: // contraflow. Heavy traffic
		case 598: // contraflow. Heavy traffic expected
		case 600: // contraflow. Traffic building up
		case 601: // contraflow. Carriageway reduced (from Q lanes) to one lane
		case 602: // contraflow. Carriageway reduced (from Q lanes) to two lanes
		case 603: // contraflow. Carriageway reduced (from Q lanes) to three lanes
		case 604: // narrow lanes. Stationary traffic
		case 605: // narrow lanes. Danger of stationary traffic
		case 606: // narrow lanes. Queuing traffic
		case 607: // narrow lanes. Danger of queuing traffic
		case 608: // narrow lanes. Slow traffic
		case 609: // narrow lanes. Slow traffic expected
		case 610: // narrow lanes. Heavy traffic
		case 611: // narrow lanes. Heavy traffic expected
		case 613: // narrow lanes. Traffic building up
		case 614: // contraflow with narrow lanes. Stationary traffic
		case 615: // contraflow with narrow lanes. Stationary traffic. Danger of stationary traffic
		case 616: // contraflow with narrow lanes. Queuing traffic
		case 617: // contraflow with narrow lanes. Danger of queuing traffic
		case 618: // contraflow with narrow lanes. Slow traffic
		case 619: // contraflow with narrow lanes. Slow traffic expected
		case 620: // contraflow with narrow lanes. Heavy traffic
		case 621: // contraflow with narrow lanes. Heavy traffic expected
		case 623: // contraflow with narrow lanes. Traffic building up
		case 651: // (Q) lanes closed. Stationary traffic for 3 km
		case 652: // (Q) lanes closed. Queuing traffic for 3 km
		case 653: // (Q) lanes closed. Slow traffic for 3 km
		case 654: // contraflow. Stationary traffic for 3 km
		case 655: // contraflow. Queuing traffic for 3 km
		case 656: // contraflow. Slow traffic for 3 km
		case 710: // (Q sets of) roadworks. Stationary traffic
		case 711: // (Q sets of) roadworks. Stationary traffic for 1 km
		case 712: // (Q sets of) roadworks. Stationary traffic for 2 km
		case 713: // (Q sets of) roadworks. Stationary traffic for 4 km
		case 714: // (Q sets of) roadworks. Stationary traffic for 6 km
		case 715: // (Q sets of) roadworks. Stationary traffic for 10 km
		case 716: // (Q sets of) roadworks. Danger of stationary traffic
		case 717: // (Q sets of) roadworks. Queuing traffic
		case 718: // (Q sets of) roadworks. Queuing traffic for 1 km
		case 719: // (Q sets of) roadworks. Queuing traffic for 2 km
		case 720: // (Q sets of) roadworks. Queuing traffic for 4 km
		case 721: // (Q sets of) roadworks. Queuing traffic for 6 km
		case 722: // (Q sets of) roadworks. Queuing traffic for 10 km
		case 723: // (Q sets of) roadworks. Danger of queuing traffic
		case 724: // (Q sets of) roadworks. Slow traffic
		case 725: // (Q sets of) roadworks. Slow traffic for 1 km
		case 726: // (Q sets of) roadworks. Slow traffic for 2 km
		case 727: // (Q sets of) roadworks. Slow traffic for 4 km
		case 728: // (Q sets of) roadworks. Slow traffic for 6 km
		case 729: // (Q sets of) roadworks. Slow traffic for 10 km
		case 730: // (Q sets of) roadworks. Slow traffic expected
		case 731: // (Q sets of) roadworks. Heavy traffic
		case 732: // (Q sets of) roadworks. Heavy traffic expected
		case 734: // (Q sets of) roadworks. Traffic building up
		case 747: // roadworks. Delays (Q)
		case 748: // roadworks. Delays (Q) expected
		case 749: // roadworks. Long delays (Q)
		case 750: // (Q sections of) resurfacing work. Stationary traffic
		case 751: // (Q sections of) resurfacing work. Stationary traffic for 1 km
		case 752: // (Q sections of) resurfacing work. Stationary traffic for 2 km
		case 753: // (Q sections of) resurfacing work. Stationary traffic for 4 km
		case 754: // (Q sections of) resurfacing work. Stationary traffic for 6 km
		case 755: // (Q sections of) resurfacing work. Stationary traffic for 10 km
		case 756: // (Q sections of) resurfacing work. Danger of stationary traffic
		case 757: // (Q sections of) resurfacing work. Queuing traffic
		case 758: // (Q sections of) resurfacing work. Queuing traffic for 1 km
		case 759: // (Q sections of) resurfacing work. Queuing traffic for 2 km
		case 760: // (Q sections of) resurfacing work. Queuing traffic for 4 km
		case 761: // (Q sections of) resurfacing work. Queuing traffic for 6 km
		case 762: // (Q sections of) resurfacing work. Queuing traffic for 10 km
		case 763: // (Q sections of) resurfacing work. Danger of queuing traffic
		case 764: // (Q sections of) resurfacing work. Slow traffic
		case 765: // (Q sections of) resurfacing work. Slow traffic for 1 km
		case 766: // (Q sections of) resurfacing work. Slow traffic for 2 km
		case 767: // (Q sections of) resurfacing work. Slow traffic for 4 km
		case 768: // (Q sections of) resurfacing work. Slow traffic for 6 km
		case 769: // (Q sections of) resurfacing work. Slow traffic for 10 km
		case 770: // (Q sections of) resurfacing work. Slow traffic expected
		case 771: // (Q sections of) resurfacing work. Heavy traffic
		case 772: // (Q sections of) resurfacing work. Heavy traffic expected
		case 774: // (Q sections of) resurfacing work. Traffic building up
		case 780: // resurfacing work. Delays (Q)
		case 781: // resurfacing work. Delays (Q) expected
		case 782: // resurfacing work. Long delays (Q)
		case 783: // (Q sets of) road marking work. Stationary traffic
		case 784: // (Q sets of) road marking work. Danger of stationary traffic
		case 785: // (Q sets of) road marking work. Queuing traffic
		case 786: // (Q sets of) road marking work. Danger of queuing traffic
		case 787: // (Q sets of) road marking work. Slow traffic
		case 788: // (Q sets of) road marking work. Slow traffic expected
		case 789: // (Q sets of) road marking work. Heavy traffic
		case 790: // (Q sets of) road marking work. Heavy traffic expected
		case 792: // (Q sets of) road marking work. Traffic building up
		case 812: // (Q sets of) roadworks. Stationary traffic for 3 km
		case 813: // (Q sets of) roadworks. Queuing traffic for 3 km
		case 814: // (Q sets of) roadworks. Slow traffic for 3 km
		case 818: // (Q sections of) resurfacing work. Stationary traffic for 3 km
		case 819: // (Q sections of) resurfacing work. Queuing traffic for 3 km
		case 820: // (Q sections of) resurfacing work. Slow traffic for 3 km
		case 825: // (Q sets of) slow moving maintenance vehicles. Stationary traffic
		case 826: // (Q sets of) slow moving maintenance vehicles. Danger of stationary traffic
		case 827: // (Q sets of) slow moving maintenance vehicles. Queuing traffic
		case 828: // (Q sets of) slow moving maintenance vehicles. Danger of queuing traffic
		case 829: // (Q sets of) slow moving maintenance vehicles. Slow traffic
		case 830: // (Q sets of) slow moving maintenance vehicles. Slow traffic expected
		case 831: // (Q sets of) slow moving maintenance vehicles. Heavy traffic
		case 832: // (Q sets of) slow moving maintenance vehicles. Heavy traffic expected
		case 834: // (Q sets of) slow moving maintenance vehicles. Traffic building up
			$roles[''][] = 'traffic';
			$roles[''][] = 'obstruction';
			break;
		case 12: // (Q) accident(s), traffic being directed around accident area
		case 61: // (Q) object(s) on roadway {something that does not neccessarily block the road or part of it}
		case 62: // (Q) burst pipe(s)
		case 63: // (Q) object(s) on the road. Danger
		case 64: // burst pipe. Danger
		case 201: // (Q) accident(s)
		case 202: // (Q) serious accident(s)
		case 203: // multi-vehicle accident (involving Q vehicles)
		case 204: // accident involving (a/Q) heavy lorr(y/ies)
		case 205: // (Q) accident(s) involving hazardous materials
		case 206: // (Q) fuel spillage accident(s)
		case 207: // (Q) chemical spillage accident(s)
		case 208: // vehicles slowing to look at (Q) accident(s)
		case 209: // (Q) accident(s) in the opposing lanes
		case 210: // (Q) shed load(s)
		case 211: // (Q) broken down vehicle(s)
		case 212: // (Q) broken down heavy lorr(y/ies)
		case 213: // (Q) vehicle fire(s)
		case 214: // (Q) incident(s)
		case 238: // (Q) accident(s). Traffic flowing freely
		case 241: // (Q) accident(s). Right lane blocked
		case 242: // (Q) accident(s). Centre lane blocked
		case 243: // (Q) accident(s). Left lane blocked
		case 244: // (Q) accident(s). Hard shoulder blocked
		case 245: // (Q) accident(s). Two lanes blocked
		case 246: // (Q) accident(s). Three lanes blocked
		case 301: // (Q) shed load(s). Traffic flowing freely
		case 304: // (Q) shed load(s). Right lane blocked
		case 305: // (Q) shed load(s). Centre lane blocked
		case 306: // (Q) shed load(s). Left lane blocked
		case 307: // (Q) shed load(s). Hard shoulder blocked
		case 308: // (Q) shed load(s). Two lanes blocked
		case 309: // (Q) shed load(s). Three lanes blocked
		case 321: // (Q) broken down vehicle(s). Traffic flowing freely
		case 324: // (Q) broken down vehicle(s). Right lane blocked
		case 325: // (Q) broken down vehicle(s). Centre lane blocked
		case 326: // (Q) broken down vehicle(s). Left lane blocked
		case 327: // (Q) broken down vehicle(s). Hard shoulder blocked
		case 328: // (Q) broken down vehicle(s). Two lanes blocked
		case 329: // (Q) broken down vehicle(s). Three lanes blocked
		case 335: // accident involving (a/Q) bus(es)
		case 336: // (Q) oil spillage accident(s)
		case 337: // (Q) overturned vehicle(s)
		case 338: // (Q) overturned heavy lorr(y/ies)
		case 339: // (Q) jackknifed trailer(s)
		case 340: // (Q) jackknifed caravan(s)
		case 341: // (Q) jackknifed articulated lorr(y/ies)
		case 342: // (Q) vehicle(s) spun around
		case 343: // (Q) earlier accident(s)
		case 344: // accident investigation work
		case 345: // (Q) secondary accident(s)
		case 346: // (Q) broken down bus(es)
		case 347: // (Q) overheight vehicle(s)
		case 359: // (Q) shed load(s). Danger
		case 370: // (Q) overturned vehicle(s). Right lane blocked
		case 371: // (Q) overturned vehicle(s). Centre lane blocked
		case 372: // (Q) overturned vehicle(s). Left lane blocked
		case 373: // (Q) overturned vehicle(s). Two lanes blocked
		case 374: // (Q) overturned vehicle(s). Three lanes blocked
		case 378: // (Q) overturned vehicle(s). Danger
		case 391: // accident investigation work. Danger
		case 392: // (Q) secondary accident(s). Danger
		case 393: // (Q) broken down vehicle(s). Danger
		case 394: // (Q) broken down heavy lorr(y/ies). Danger
		case 397: // rescue and recovery work in progress
		case 856: // construction traffic merging. Danger
		case 857: // (Q) unprotected accident area(s)
		case 858: // danger of (Q) unprotected accident area(s)
		case 859: // (Q) unlit vehicle(s) on the road
		case 860: // danger of (Q) unlit vehicle(s) on the road
		case 861: // snow and ice debris
		case 862: // danger of snow and ice debris
		case 897: // people throwing objects onto the road. Danger
		case 900: // flooding expected
		case 901: // (Q) obstruction(s) on roadway {something that does block the road or part of it}
		case 902: // (Q) obstructions on the road. Danger
		case 903: // spillage on the road
		case 904: // storm damage
		case 905: // (Q) fallen trees
		case 906: // (Q) fallen trees. Danger
		case 907: // flooding
		case 908: // flooding. Danger
		case 909: // flash floods
		case 910: // danger of flash floods
		case 911: // avalanches
		case 912: // avalanche risk
		case 913: // rockfalls
		case 914: // landslips
		case 915: // earthquake damage
		case 916: // road surface in poor condition
		case 917: // subsidence
		case 918: // (Q) collapsed sewer(s)
		case 919: // burst water main
		case 920: // gas leak
		case 921: // serious fire
		case 922: // animals on roadway
		case 923: // animals on the road. Danger
		case 924: // clearance work
		case 927: // (Q) fallen tree(s). Passable with care
		case 936: // flooding. Traffic flowing freely
		case 942: // flooding. Passable with care
		case 944: // avalanches. Passable with care (above Q hundred metres)
		case 946: // rockfalls. Passable with care
		case 948: // landslips. Passable with care
		case 955: // subsidence. Passable with care
		case 972: // storm damage expected
		case 973: // fallen power cables
		case 974: // sewer overflow
		case 975: // ice build-up
		case 976: // mud slide
		case 977: // grass fire
		case 978: // air crash
		case 979: // rail crash
		case 981: // (Q) obstructions on the road. Passable with care
		case 983: // spillage on the road. Passable with care
		case 984: // spillage on the road. Danger
		case 985: // storm damage. Passable with care
		case 986: // storm damage. Danger
		case 988: // fallen power cables. Passable with care
		case 989: // fallen power cables. Danger
		case 990: // sewer overflow. Danger
		case 991: // flash floods. Danger
		case 992: // avalanches. Danger
		case 994: // avalanche risk. Danger
		case 996: // ice build-up. Passable with care (above Q hundred metres)
		case 997: // ice build-up. Single alternate traffic
		case 998: // rockfalls. Danger
		case 999: // landslips. Danger
		case 1000: // earthquake damage. Danger
		case 1001: // hazardous driving conditions (above Q hundred metres)
		case 1002: // danger of aquaplaning
		case 1003: // slippery road (above Q hundred metres)
		case 1004: // mud on road
		case 1005: // leaves on road
		case 1006: // ice (above Q hundred metres)
		case 1007: // danger of ice (above Q hundred metres)
		case 1008: // black ice (above Q hundred metres)
		case 1009: // freezing rain (above Q hundred metres)
		case 1010: // wet and icy roads (above Q hundred metres)
		case 1011: // slush (above Q hundred metres)
		case 1012: // snow on the road (above Q hundred metres)
		case 1013: // packed snow (above Q hundred metres)
		case 1014: // fresh snow (above Q hundred metres)
		case 1015: // deep snow (above Q hundred metres)
		case 1016: // snow drifts (above Q hundred metres)
		case 1017: // slippery due to spillage on roadway
		case 1018: // slippery road (above Q hundred metres) due to snow
		case 1019: // slippery road (above Q hundred metres) due to frost
		case 1026: // subsidence. Danger
		case 1030: // sewer collapse. Danger
		case 1031: // burst water main. Danger
		case 1032: // gas leak. Danger
		case 1033: // serious fire. Danger
		case 1034: // clearance work. Danger
		case 1037: // extremely hazardous driving conditions (above Q hundred metres)
		case 1038: // difficult driving conditions (above Q hundred metres)
		case 1041: // surface water hazard
		case 1042: // loose sand on road
		case 1043: // loose chippings
		case 1044: // oil on road
		case 1045: // petrol on road
		case 1046: // ice expected (above Q hundred metres)
		case 1047: // icy patches (above Q hundred metres)
		case 1048: // danger of icy patches (above Q hundred metres)
		case 1049: // icy patches expected (above Q hundred metres)
		case 1050: // danger of black ice (above Q hundred metres)
		case 1051: // black ice expected (above Q hundred metres)
		case 1052: // freezing rain expected (above Q hundred metres)
		case 1053: // snow drifts expected (above Q hundred metres)
		case 1054: // slippery due to loose sand on roadway
		case 1055: // mud on road. Danger
		case 1056: // loose chippings. Danger
		case 1057: // oil on road. Danger
		case 1058: // petrol on road. Danger
		case 1059: // road surface in poor condition. Danger
		case 1060: // icy patches (above Q hundred metres) on bridges
		case 1061: // danger of icy patches (above Q hundred metres) on bridges
		case 1062: // icy patches (above Q hundred metres) on bridges, in shaded areas and on slip roads
		case 1066: // rescue and recovery work in progress. Danger
		case 1067: // large animals on roadway
		case 1068: // herds of animals on roadway
		case 1073: // extremely hazardous driving conditions expected (above Q  hundred meters)
		case 1074: // freezing rain expected (above Q hundred metres)
		case 1084: // house fire
		case 1085: // forest fire
		case 1086: // vehicle stuck under bridge
		case 1090: // volcano eruption warning
			$roles[''][] = 'danger';
			break;
		case 200: // multi vehicle pile up. Delays (Q)
		case 302: // (Q) shed load(s). Traffic building up
		case 215: // (Q) accident(s). Stationary traffic
		case 216: // (Q) accident(s). Stationary traffic for 1 km
		case 217: // (Q) accident(s). Stationary traffic for 2 km
		case 218: // (Q) accident(s). Stationary traffic for 4 km
		case 219: // (Q) accident(s). Stationary traffic for 6 km
		case 220: // (Q) accident(s). Stationary traffic for 10 km
		case 221: // (Q) accident(s). Danger of stationary traffic
		case 222: // (Q) accident(s). Queuing traffic
		case 223: // (Q) accident(s). Queuing traffic for 1 km
		case 224: // (Q) accident(s). Queuing traffic for 2 km
		case 225: // (Q) accident(s). Queuing traffic for 4 km
		case 226: // (Q) accident(s). Queuing traffic for 6 km
		case 227: // (Q) accident(s). Queuing traffic for 10 km
		case 228: // (Q) accident(s). Danger of queuing traffic
		case 229: // (Q) accident(s). Slow traffic
		case 230: // (Q) accident(s). Slow traffic for 1 km
		case 231: // (Q) accident(s). Slow traffic for 2 km
		case 232: // (Q) accident(s). Slow traffic for 4 km
		case 233: // (Q) accident(s). Slow traffic for 6 km
		case 234: // (Q) accident(s). Slow traffic for 10 km
		case 235: // (Q) accident(s). Slow traffic expected
		case 236: // (Q) accident(s). Heavy traffic
		case 237: // (Q) accident(s). Heavy traffic expected
		case 239: // (Q) accident(s). Traffic building up
		case 247: // accident. Delays (Q)
		case 248: // accident. Delays (Q) expected
		case 249: // accident. Long delays (Q)
		case 250: // vehicles slowing to look at (Q) accident(s). Stationary traffic
		case 251: // vehicles slowing to look at (Q) accident(s). Stationary traffic for 1 km
		case 252: // vehicles slowing to look at (Q) accident(s). Stationary traffic for 2 km
		case 253: // vehicles slowing to look at (Q) accident(s). Stationary traffic for 4 km
		case 254: // vehicles slowing to look at (Q) accident(s). Stationary traffic for 6 km
		case 255: // vehicles slowing to look at (Q) accident(s). Stationary traffic for 10 km
		case 256: // vehicles slowing to look at (Q) accident(s). Danger of stationary traffic
		case 257: // vehicles slowing to look at (Q) accident(s). Queuing traffic
		case 258: // vehicles slowing to look at (Q) accident(s). Queuing traffic for 1 km
		case 259: // vehicles slowing to look at (Q) accident(s). Queuing traffic for 2 km
		case 260: // vehicles slowing to look at (Q) accident(s). Queuing traffic for 4 km
		case 261: // vehicles slowing to look at (Q) accident(s). Queuing traffic for 6 km
		case 262: // vehicles slowing to look at (Q) accident(s). Queuing traffic for 10 km
		case 263: // vehicles slowing to look at (Q) accident(s). Danger of queuing traffic
		case 264: // vehicles slowing to look at (Q) accident(s). Slow traffic
		case 265: // vehicles slowing to look at (Q) accident(s). Slow traffic for 1 km
		case 266: // vehicles slowing to look at (Q) accident(s). Slow traffic for 2 km
		case 267: // vehicles slowing to look at (Q) accident(s). Slow traffic for 4 km
		case 268: // vehicles slowing to look at (Q) accident(s). Slow traffic for 6 km
		case 269: // vehicles slowing to look at (Q) accident(s). Slow traffic for 10 km
		case 270: // vehicles slowing to look at (Q) accident(s). Slow traffic expected
		case 271: // vehicles slowing to look at (Q) accident(s). Heavy traffic
		case 272: // vehicles slowing to look at (Q) accident(s). Heavy traffic expected
		case 274: // vehicles slowing to look at (Q) accident(s). Traffic building up
		case 275: // vehicles slowing to look at accident. Delays (Q)
		case 276: // vehicles slowing to look at accident. Delays (Q) expected
		case 277: // vehicles slowing to look at accident. Long delays (Q)
		case 278: // (Q) shed load(s). Stationary traffic
		case 279: // (Q) shed load(s). Stationary traffic for 1 km
		case 280: // (Q) shed load(s). Stationary traffic for 2 km
		case 281: // (Q) shed load(s). Stationary traffic for 4 km
		case 282: // (Q) shed load(s). Stationary traffic for 6 km
		case 283: // (Q) shed load(s). Stationary traffic for 10 km
		case 284: // (Q) shed load(s). Danger of stationary traffic
		case 285: // (Q) shed load(s). Queuing traffic
		case 286: // (Q) shed load(s). Queuing traffic for 1 km
		case 287: // (Q) shed load(s). Queuing traffic for 2 km
		case 288: // (Q) shed load(s). Queuing traffic for 4 km
		case 289: // (Q) shed load(s). Queuing traffic for 6 km
		case 290: // (Q) shed load(s). Queuing traffic for 10 km
		case 291: // (Q) shed load(s). Danger of queuing traffic
		case 292: // (Q) shed load(s). Slow traffic
		case 293: // (Q) shed load(s). Slow traffic for 1 km
		case 294: // (Q) shed load(s). Slow traffic for 2 km
		case 295: // (Q) shed load(s). Slow traffic for 4 km
		case 296: // (Q) shed load(s). Slow traffic for 6 km
		case 297: // (Q) shed load(s). Slow traffic for 10 km
		case 298: // (Q) shed load(s). Slow traffic expected
		case 299: // (Q) shed load(s). Heavy traffic
		case 300: // (Q) shed load(s). Heavy traffic expected
		case 310: // shed load. Delays (Q)
		case 311: // shed load. Delays (Q) expected
		case 312: // shed load. Long delays (Q)
		case 313: // (Q) broken down vehicle(s). Stationary traffic
		case 314: // (Q) broken down vehicle(s). Danger of stationary traffic
		case 315: // (Q) broken down vehicle(s). Queuing traffic
		case 316: // (Q) broken down vehicle(s). Danger of queuing traffic
		case 317: // (Q) broken down vehicle(s). Slow traffic
		case 318: // (Q) broken down vehicle(s). Slow traffic expected
		case 319: // (Q) broken down vehicle(s). Heavy traffic
		case 320: // (Q) broken down vehicle(s). Heavy traffic expected
		case 322: // (Q) broken down vehicle(s).Traffic building up
		case 330: // broken down vehicle. Delays (Q)
		case 331: // broken down vehicle. Delays (Q) expected
		case 332: // broken down vehicle. Long delays (Q)
		case 348: // (Q) accident(s). Stationary traffic for 3 km
		case 349: // (Q) accident(s). Queuing traffic for 3 km
		case 350: // (Q) accident(s). Slow traffic for 3 km
		case 352: // vehicles slowing to look at (Q) accident(s). Stationary traffic for 3 km
		case 353: // vehicles slowing to look at (Q) accident(s). Queuing traffic for 3 km
		case 354: // vehicles slowing to look at (Q) accident(s). Slow traffic for 3 km
		case 355: // vehicles slowing to look at (Q) accident(s). Danger
		case 356: // (Q) shed load(s). Stationary traffic for 3 km
		case 357: // (Q) shed load(s). Queuing traffic for 3 km
		case 358: // (Q) shed load(s). Slow traffic for 3 km
		case 360: // (Q) overturned vehicle(s). Stationary traffic
		case 361: // (Q) overturned vehicle(s). Danger of stationary traffic
		case 362: // (Q) overturned vehicle(s). Queuing traffic
		case 363: // (Q) overturned vehicle(s). Danger of queuing traffic
		case 364: // (Q) overturned vehicle(s). Slow traffic
		case 365: // (Q) overturned vehicle(s). Slow traffic expected
		case 366: // (Q) overturned vehicle(s). Heavy traffic
		case 367: // (Q) overturned vehicle(s). Heavy traffic expected
		case 368: // (Q) overturned vehicle(s). Traffic building up
		case 375: // overturned vehicle. Delays (Q)
		case 376: // overturned vehicle. Delays (Q) expected
		case 377: // overturned vehicle. Long delays (Q)
		case 379: // Stationary traffic due to (Q) earlier accident(s)
		case 380: // Danger of stationary traffic due to (Q) earlier accident(s)
		case 381: // Queuing traffic due to (Q) earlier accident(s)
		case 382: // Danger of queuing traffic due to (Q) earlier accident(s)
		case 383: // Slow traffic due to (Q) earlier accident(s)
		case 385: // Heavy traffic due to (Q) earlier accident(s)
		case 387: // Traffic building up due to (Q) earlier accident(s)
		case 388: // Delays (Q) due to earlier accident
		case 390: // Long delays (Q) due to earlier accident
		case 928: // flooding. Stationary traffic
		case 929: // flooding. Danger of stationary traffic
		case 930: // flooding. Queuing traffic
		case 931: // flooding. Danger of queuing traffic
		case 932: // flooding. Slow traffic
		case 933: // flooding. Slow traffic expected
		case 934: // flooding. Heavy traffic
		case 935: // flooding. Heavy traffic expected
		case 937: // flooding. Traffic building up
		case 939: // flooding. Delays (Q)
		case 940: // flooding. Delays (Q) expected
		case 941: // flooding. Long delays (Q)
		case 958: // burst water main. Delays (Q)
		case 959: // burst water main. Delays (Q) expected
		case 960: // burst water main. Long delays (Q)
		case 962: // gas leak. Delays (Q)
		case 963: // gas leak. Delays (Q) expected
		case 964: // gas leak. Long delays (Q)
		case 966: // serious fire. Delays (Q)
		case 967: // serious fire. Delays (Q) expected
		case 968: // serious fire. Long delays (Q)
		case 1027: // sewer collapse. Delays (Q)
		case 1028: // sewer collapse. Delays (Q) expected
		case 1029: // sewer collapse. Long delays (Q)
			$roles[''][] = 'danger';
			$roles[''][] = 'traffic';
			break;
		case 351: // (Q) accident(s) in roadworks area
		case 950: // subsidence. Single alternate line traffic
		case 951: // subsidence. Carriageway reduced (from Q lanes) to one lane
		case 952: // subsidence. Carriageway reduced (from Q lanes) to two lanes
		case 953: // subsidence. Carriageway reduced (from Q lanes) to three lanes
		case 954: // subsidence. Contraflow in operation
		case 1021: // snow on the road. Carriageway reduced (from Q lanes) to one lane
		case 1022: // snow on the road. Carriageway reduced (from Q lanes) to two lanes
		case 1023: // snow on the road. Carriageway reduced (from Q lanes) to three lanes
			$roles[''][] = 'danger';
			$roles[''][] = 'obstruction';
			break;
		case 240: // road closed due to (Q) accident(s)
		case 303: // blocked by (Q) shed load(s)
		case 323: // blocked by (Q) broken down vehicle(s).
		case 369: // blocked by (Q) overturned vehicle(s)
		case 925: // blocked by storm damage
		case 926: // blocked by (Q) fallen trees
		case 938: // closed due to flooding
		case 943: // closed due to avalanches
		case 945: // closed due to rockfalls
		case 947: // road closed due to landslips
		case 949: // closed due to subsidence
		case 956: // closed due to sewer collapse
		case 957: // road closed due to burst water main
		case 961: // closed due to gas leak
		case 965: // closed due to serious fire
		case 980: // blocked by (Q) obstruction(s) on the road
		case 982: // blocked due to spillage on roadway
		case 987: // blocked by fallen power cables
		case 993: // closed due to avalanche risk
		case 995: // closed due to ice build-up
		case 1020: // road blocked by snow (above Q hundred metres)
			$roles[''][] = 'danger';
			$roles[''][] = 'closed';
			break;
		case 16: // closed, rescue and recovery work in progress
		case 24: // bridge closed
		case 25: // tunnel closed
		case 26: // bridge blocked
		case 27: // tunnel blocked
		case 401: // closed
		case 402: // blocked
		case 403: // closed for heavy vehicles (over Q)
		case 404: // no through traffic for heavy lorries (over Q)
		case 405: // no through traffic
		case 433: // closed ahead. Traffic flowing freely
		case 461: // blocked ahead. Traffic flowing freely
		case 469: // closed ahead
		case 470: // blocked ahead
		case 664: // carriageway closed
		case 665: // both directions closed
		case 969: // closed for clearance work
		case 1035: // impassable (above Q hundred metres)
		case 1036: // almost impassable (above Q hundred metres)
		case 1063: // impassable for heavy vehicles (over Q)
		case 1064: // impassable (above Q hundred metres) for vehicles with trailers
		case 1075: // danger of road being blocked by snow (above Q hundred metres)
			$roles[''][] = 'closed';
			break;
		case 83: // closed ahead. Heavy traffic expected
		case 410: // closed ahead. Stationary traffic
		case 411: // closed ahead. Stationary traffic for 1 km
		case 412: // closed ahead. Stationary traffic for 2 km
		case 413: // closed ahead. Stationary traffic for 4 km
		case 414: // closed ahead. Stationary traffic for 6 km
		case 415: // closed ahead. Stationary traffic for 10 km
		case 416: // closed ahead. Danger of stationary traffic
		case 417: // closed ahead. Queuing traffic
		case 418: // closed ahead. Queuing traffic for 1 km
		case 419: // closed ahead. Queuing traffic for 2 km
		case 420: // closed ahead. Queuing traffic for 4 km
		case 421: // closed ahead. Queuing traffic for 6 km
		case 422: // closed ahead. Queuing traffic for 10 km
		case 423: // closed ahead. Danger of queuing traffic
		case 424: // closed ahead. Slow traffic
		case 425: // closed ahead. Slow traffic for 1 km
		case 426: // closed ahead. Slow traffic for 2 km
		case 427: // closed ahead. Slow traffic for 4 km
		case 428: // closed ahead. Slow traffic for 6 km
		case 429: // closed ahead. Slow traffic for 10 km
		case 430: // closed ahead. Slow traffic expected
		case 431: // closed ahead. Heavy traffic
		case 432: // closed ahead. Heavy traffic expected
		case 434: // closed ahead. Traffic building up
		case 435: // closed ahead. Delays (Q)
		case 436: // closed ahead. Delays (Q) expected
		case 437: // closed ahead. Long delays (Q)
		case 438: // blocked ahead. Stationary traffic
		case 439: // blocked ahead. Stationary traffic for 1 km
		case 440: // blocked ahead. Stationary traffic for 2 km
		case 441: // blocked ahead. Stationary traffic for 4 km
		case 442: // blocked ahead. Stationary traffic for 6 km
		case 443: // blocked ahead. Stationary traffic for 10 km
		case 444: // blocked ahead. Danger of stationary traffic
		case 445: // blocked ahead. Queuing traffic
		case 446: // blocked ahead. Queuing traffic for 1 km
		case 447: // blocked ahead. Queuing traffic for 2 km
		case 448: // blocked ahead. Queuing traffic for 4 km
		case 449: // blocked ahead. Queuing traffic for 6 km
		case 450: // blocked ahead. Queuing traffic for 10 km
		case 451: // blocked ahead. Danger of queuing traffic
		case 452: // blocked ahead. Slow traffic
		case 453: // blocked ahead. Slow traffic for 1 km
		case 454: // blocked ahead. Slow traffic for 2 km
		case 455: // blocked ahead. Slow traffic for 4 km
		case 456: // blocked ahead. Slow traffic for 6 km
		case 457: // blocked ahead. Slow traffic for 10 km
		case 458: // blocked ahead. Slow traffic expected
		case 459: // blocked ahead. Heavy traffic
		case 460: // blocked ahead. Heavy traffic expected
		case 462: // blocked ahead. Traffic building up
		case 463: // blocked ahead. Delays (Q)
		case 464: // blocked ahead. Delays (Q) expected
		case 465: // blocked ahead. Long delays (Q)
		case 495: // closed ahead. Stationary traffic for 3 km
		case 496: // closed ahead. Queuing traffic for 3 km
		case 497: // closed ahead. Slow traffic for 3 km
		case 498: // blocked ahead. Stationary traffic for 3 km
		case 499: // blocked ahead. Queuing traffic for 3 km
		case 626: // blocked ahead. Slow traffic for 3 km
			$roles[''][] = 'closed';
			$roles[''][] = 'traffic';
			break;
		case 735: // closed due to (Q sets of) roadworks
		case 799: // closed for bridge demolition work (at Q bridges)
			$roles[''][] = 'closed';
			$roles[''][] = 'obstruction';
			break;
		case 406: // (Q th) entry slip road closed
		case 471: // (Q) entry slip road(s) closed
		case 472: // (Q th) entry slip road blocked
		case 473: // entry blocked
			$roles['entry'][] = 'closed';
			break;
		case 407: // (Q th) exit slip road closed
		case 474: // (Q) exit slip road(s) closed
		case 475: // (Q th) exit slip road blocked
		case 476: // exit blocked
			$roles['exit'][] = 'closed';
			break;
		case 408: // slip roads closed
		case 409: // slip road restrictions
		case 477: // slip roads blocked
			$roles['entry'][] = 'closed';
			$roles['exit'][] = 'closed';
			break;
		case 1990: // car park closed (until Q)
			$roles['parking'][] = 'closed';
			break;
		case 22: // service area, fuel station closed
			$roles['fuel'][] = 'closed';
			break;
		case 23: // service area, restaurant closed
			$roles['restaurant'][] = 'closed';
			break;
		case 20: // service area overcrowded, drive to another service area
			$roles['parking'][] = 'closed';
			$roles['fuel'][] = 'closed';
			$roles['restaurant'][] = 'closed';
			break;
		case 39: // reopening of bridge expected (Q)
		case 40: // smog alert ended
		case 57: // normal traffic expected
		case 88: // traffic congestion forecast withdrawn
		case 89: // message cancelled
		case 124: // traffic flowing freely (with average speeds Q)
		case 126: // no problems to report
		case 127: // traffic congestion cleared
		case 128: // message cancelled
		case 135: // traffic easing
		case 137: // traffic lighter than normal (with average speeds Q)
		case 141: // all accidents cleared, no problems to report
		case 333: // accident cleared
		case 334: // message cancelled
		case 395: // road cleared
		case 396: // incident cleared
		case 399: // message cancelled
		case 467: // reopened
		case 468: // message cancelled
		case 624: // lane closures removed
		case 625: // message cancelled
		case 630: // open
		case 631: // road cleared
		case 634: // all carriageways reopened
		case 635: // motor vehicle restrictions lifted
		case 636: // traffic restrictions lifted  {reopened for all traffic}
		case 657: // lane blockages cleared
		case 658: // contraflow removed
		case 659: // (Q person) carpool restrictions lifted
		case 660: // lane restrictions lifted
		case 661: // use of hard shoulder allowed
		case 662: // normal lane regulations restored
		case 663: // all carriageways cleared
		case 671: // bus lane available for carpools (with at least Q occupants)
		case 672: // message cancelled
		case 673: // message cancelled
		case 680: // reopened for through traffic
		case 800: // roadworks cleared
		case 801: // message cancelled
		case 854: // maintenance work cleared
		case 855: // road layout unchanged
		case 898: // obstruction warning withdrawn
		case 899: // clearance work in progress, road free again
		case 970: // road free again
		case 971: // message cancelled
		case 1024: // conditions of road surface improved
		case 1025: // message cancelled
		case 1039: // passable with care (up to Q hundred metres)
		case 1040: // passable (up to Q hundred metres)
		case 1065: // driving conditions improved
		case 1069: // skid hazard reduced
		case 1070: // snow cleared
		case 1071: // road conditions forecast withdrawn
		case 1072: // message cancelled
		case 1584: // traffic has returned to normal
		case 1585: // message cancelled
		case 1587: // air raid warning cancelled
		case 1588: // civil emergency cancelled
		case 1589: // message cancelled
		case 2028: // message cancelled
		case 2029: // message cancelled
		case 2030: // message cancelled
		case 2032: // message cancelled
		case 2033: // message cancelled
		case 2034: // message cancelled
		case 2035: // message cancelled
		case 2038: // message cancelled
		case 2039: // message cancelled
		case 2040: // message cancelled
		case 2041: // nothing to report
		case 2047: // (null message)  {completely silent message, see protocol, sect. 3.5.4}
			$roles[''][] = 'clear';
			break;
		case 632: // entry reopened
			$roles['entry'][] = 'clear';
			break;
		case 633: // exit reopened
			$roles['exit'][] = 'clear';
			break;
		case 466: // slip roads reopened
			$roles['entry'][] = 'clear';
			$roles['exit'][] = 'clear';
			break;
		case 36: // fuel station reopened
			$roles['fuel'][] = 'clear';
			break;
		case 37: // restaurant reopened
			$roles['restaurant'][] = 'clear';
			break;
		case 478: // connecting carriageway closed
		case 479: // parallel carriageway closed
		case 480: // right-hand parallel carriageway closed
		case 481: // left-hand parallel carriageway closed
		case 485: // connecting carriageway blocked
		case 486: // parallel carriageway blocked
		case 487: // right-hand parallel carriageway blocked
		case 488: // left-hand parallel carriageway blocked
		case 627: // no motor vehicles without catalytic converters
		case 628: // no motor vehicles with even-numbered registration plates
		case 629: // no motor vehicles with odd-numbered registration plates
		case 647: // (Q person) carpool lane in operation
		case 650: // carpool restrictions changed (to Q persons per vehicle)
		case 1079: // temperature falling rapidly (to Q)
		case 1080: // extreme heat (up to Q)
		case 1081: // extreme cold (of Q)
		case 1082: // less extreme temperatures
		case 1083: // current temperature (Q)
		case 1101: // heavy snowfall (Q)
		case 1102: // heavy snowfall (Q). Visibility reduced to <30 m
		case 1103: // heavy snowfall (Q). Visibility reduced to <50 m
		case 1104: // snowfall (Q)
		case 1105: // snowfall (Q). Visibility reduced to <100 m
		case 1106: // hail (visibility reduced to Q)
		case 1107: // sleet (visibility reduced to Q)
		case 1108: // thunderstorms (visibility reduced to Q)
		case 1109: // heavy rain (Q)
		case 1110: // heavy rain (Q). Visibility reduced to <30 m
		case 1111: // heavy rain (Q). Visibility reduced to <50 m
		case 1112: // rain (Q)
		case 1113: // rain (Q). Visibility reduced to <100 m
		case 1114: // showers (visibility reduced to Q)
		case 1115: // heavy frost
		case 1116: // frost
		case 1117: // (Q probability of) overcast weather
		case 1118: // (Q probability of) mostly cloudy
		case 1119: // (Q probability of) partly cloudy
		case 1120: // (Q probability of) sunny periods
		case 1121: // (Q probability of) clear weather
		case 1122: // (Q probability of) sunny weather
		case 1123: // (Q probability of) mostly dry weather
		case 1124: // (Q probability of) dry weather
		case 1125: // sunny periods and with (Q probability of) showers
		case 1126: // weather situation improved
		case 1127: // message cancelled
		case 1128: // winter storm (visibility reduced to Q)
		case 1129: // (Q probability of) winter storm
		case 1130: // blizzard (visibility reduced to Q)
		case 1131: // (Q probability of) blizzard
		case 1132: // damaging hail (visibility reduced to Q)
		case 1133: // (Q probability of) damaging hail
		case 1134: // heavy snowfall. Visibility reduced (to Q)
		case 1135: // snowfall. Visibility reduced (to Q)
		case 1136: // heavy rain. Visibility reduced (to Q)
		case 1137: // rain. Visibility reduced (to Q)
		case 1138: // severe weather warnings cancelled
		case 1139: // message cancelled
		case 1140: // weather forecast withdrawn
		case 1141: // fog forecast withdrawn
		case 1143: // slippery road expected (above Q hundred metres)
		case 1151: // (Q probability of) heavy snowfall
		case 1152: // (Q probability of) snowfall
		case 1153: // (Q probability of) hail
		case 1154: // (Q probability of) sleet
		case 1155: // (Q probability of) thunderstorms
		case 1156: // (Q probability of) heavy rain
		case 1157: // (Q probability of) rain
		case 1158: // (Q probability of) showers
		case 1159: // (Q probability of) heavy frost
		case 1160: // (Q probability of) frost
		case 1165: // rain changing to snow
		case 1166: // snow changing to rain
		case 1170: // heavy snowfall (Q) expected
		case 1171: // heavy rain (Q) expected
		case 1172: // weather expected to improve
		case 1173: // blizzard (with visibility reduced to Q) expected
		case 1174: // damaging hail (with visibility reduced to Q) expected
		case 1175: // reduced visibility (to Q) expected
		case 1176: // freezing fog expected (with visibility reduced to Q). Danger of slippery roads
		case 1177: // dense fog (with visibility reduced to Q) expected
		case 1178: // patchy fog (with visibility reduced to Q) expected
		case 1179: // visibility expected to improve
		case 1180: // adverse weather warning withdrawn
		case 1190: // severe smog
		case 1191: // severe exhaust pollution
		case 1201: // tornadoes
		case 1202: // hurricane force winds (Q)
		case 1203: // gales (Q)
		case 1204: // storm force winds (Q)
		case 1205: // strong winds (Q)
		case 1206: // moderate winds (Q)
		case 1207: // light winds (Q)
		case 1208: // calm weather
		case 1209: // gusty winds (Q)
		case 1210: // crosswinds (Q)
		case 1211: // strong winds (Q) affecting high-sided vehicles
		case 1212: // closed for high-sided vehicles due to strong winds (Q)
		case 1213: // strong winds easing
		case 1214: // message cancelled
		case 1215: // restrictions for high-sided vehicles lifted
		case 1216: // tornado watch cancelled
		case 1217: // tornado warning ended
		case 1218: // wind forecast withdrawn
		case 1219: // message cancelled
		case 1251: // (Q probability of) tornadoes
		case 1252: // hurricane force winds (Q)
		case 1253: // gales (Q)
		case 1254: // storm force winds (Q)
		case 1255: // strong winds (Q)
		case 1256: // strong wind forecast withdrawn
		case 1300: // snowfall and fog (visibility reduced to Q) expected
		case 1301: // dense fog (visibility reduced to Q)
		case 1302: // dense fog. Visibility reduced to <30 m
		case 1303: // dense fog. Visibility reduced to <50 m
		case 1304: // fog (visibility reduced to Q)
		case 1305: // fog. Visibility reduced to <100 m
		case 1306: // (Q probability of) fog
		case 1307: // patchy fog (visibility reduced to Q)
		case 1308: // freezing fog (visibility reduced to Q)
		case 1309: // smoke hazard (visibility reduced to Q)
		case 1310: // blowing dust (visibility reduced to Q)
		case 1311: // (Q probability of) severe exhaust pollution
		case 1312: // snowfall and fog (visibility reduced to Q)
		case 1313: // visibility improved
		case 1314: // message cancelled
		case 1315: // (Q probability of) dense fog
		case 1316: // (Q probability of) patchy fog
		case 1317: // (Q probability of) freezing fog
		case 1318: // visibility reduced (to Q)
		case 1319: // visibility reduced to <30 m
		case 1320: // visibility reduced to <50 m
		case 1321: // visibility reduced to <100 m
		case 1322: // white out (visibility reduced to Q)
		case 1323: // blowing snow (visibility reduced to Q)
		case 1324: // spray hazard (visibility reduced to Q)
		case 1325: // low sun glare
		case 1326: // sandstorms (visibility reduced to Q)
		case 1327: // (Q probability of) sandstorms
		case 1328: // (Q probability of) air quality: good
		case 1329: // (Q probability of) air quality: fair
		case 1330: // (Q probability of) air quality: poor
		case 1331: // (Q probability of) air quality: very poor
		case 1332: // smog alert
		case 1333: // (Q probability of) smog
		case 1334: // (Q probability of) pollen count: high
		case 1335: // (Q probability of) pollen count: medium
		case 1336: // (Q probability of) pollen count: low
		case 1337: // freezing fog (visibility reduced to Q). Slippery roads
		case 1338: // no motor vehicles due to smog alert
		case 1339: // air quality improved
		case 1340: // swarms of insects (visibility reduced to Q)
		case 1345: // fog clearing
		case 1346: // fog forecast withdrawn
		case 1351: // maximum temperature (of Q)
		case 1352: // hot, (maximum temperature Q)
		case 1353: // warm, (maximum temperature Q)
		case 1354: // mild, (maximum temperature Q)
		case 1355: // cool, (maximum temperature Q)
		case 1356: // cold, (maximum temperature Q)
		case 1357: // very cold, (maximum temperature Q)
		case 1358: // message cancelled
		case 1359: // temperature rising (to Q)
		case 1360: // temperature falling rapidly (to Q)
		case 1361: // temperature (Q)
		case 1362: // effective temperature, with wind chill (Q)
		case 1364: // extreme heat (up to Q)
		case 1365: // extreme cold (of Q)
		case 1401: // minimum temperature (of Q)
		case 1402: // very warm (minimum temperature Q)
		case 1403: // warm (minimum temperature Q)
		case 1404: // mild (minimum temperature Q)
		case 1405: // cool (minimum temperature Q)
		case 1406: // cold (minimum temperature Q)
		case 1407: // very cold (minimum temperature Q)
		case 1408: // less extreme temperatures expected
		case 1449: // emergency training in progress
		case 1450: // international sports meeting
		case 1451: // match
		case 1452: // tournament
		case 1453: // athletics meeting
		case 1454: // ball game
		case 1455: // boxing tournament
		case 1456: // bull fight
		case 1457: // cricket match
		case 1458: // cycle race
		case 1459: // football match
		case 1460: // golf tournament
		case 1461: // marathon
		case 1462: // race meeting
		case 1463: // rugby match
		case 1464: // show jumping
		case 1465: // tennis tournament
		case 1466: // water sports meeting
		case 1467: // winter sports meeting
		case 1468: // funfair
		case 1469: // trade fair
		case 1470: // procession
		case 1471: // sightseers obstructing access
		case 1472: // people on roadway
		case 1473: // children on roadway
		case 1474: // cyclists on roadway
		case 1475: // strike
		case 1476: // security incident
		case 1477: // police checkpoint
		case 1478: // terrorist incident
		case 1479: // gunfire on roadway, danger
		case 1480: // civil emergency
		case 1481: // air raid, danger
		case 1482: // people on roadway. Danger
		case 1483: // children on roadway. Danger
		case 1484: // cyclists on roadway. Danger
		case 1485: // closed due to security incident
		case 1486: // security incident. Delays (Q)
		case 1487: // security incident. Delays (Q) expected
		case 1488: // security incident. Long delays (Q)
		case 1489: // police checkpoint. Delays (Q)
		case 1490: // police checkpoint. Delays (Q) expected
		case 1491: // police checkpoint. Long delays (Q)
		case 1492: // security alert withdrawn
		case 1493: // sports traffic cleared
		case 1494: // evacuation
		case 1495: // evacuation. Heavy traffic
		case 1496: // traffic disruption cleared
		case 1497: // military training in progress
		case 1498: // police activity ongoing
		case 1499: // medical emergency ongoing
		case 1500: // child abduction in progress
		case 1501: // major event
		case 1502: // sports event meeting
		case 1503: // show
		case 1504: // festival
		case 1505: // exhibition
		case 1506: // fair
		case 1507: // market
		case 1508: // ceremonial event
		case 1509: // state occasion
		case 1510: // parade
		case 1511: // crowd
		case 1512: // march
		case 1513: // demonstration
		case 1514: // public disturbance
		case 1515: // security alert
		case 1516: // bomb alert
		case 1517: // major event. Stationary traffic
		case 1518: // major event. Danger of stationary traffic
		case 1519: // major event. Queuing traffic
		case 1520: // major event. Danger of queuing traffic
		case 1521: // major event. Slow traffic
		case 1522: // major event. Slow traffic expected
		case 1523: // major event. Heavy traffic
		case 1524: // major event. Heavy traffic expected
		case 1525: // major event. Traffic flowing freely
		case 1526: // major event. Traffic building up
		case 1527: // closed due to major event
		case 1528: // major event. Delays (Q)
		case 1529: // major event. Delays (Q) expected
		case 1530: // major event. Long delays (Q)
		case 1531: // sports meeting. Stationary traffic
		case 1532: // sports meeting. Danger of stationary traffic
		case 1533: // sports meeting. Queuing traffic
		case 1534: // sports meeting. Danger of queuing traffic
		case 1535: // sports meeting. Slow traffic
		case 1536: // sports meeting. Slow traffic expected
		case 1537: // sports meeting. Heavy traffic
		case 1538: // sports meeting. Heavy traffic expected
		case 1539: // sports meeting. Traffic flowing freely
		case 1540: // sports meeting. Traffic building up
		case 1541: // closed due to sports meeting
		case 1542: // sports meeting. Delays (Q)
		case 1543: // sports meeting. Delays (Q) expected
		case 1544: // sports meeting. Long delays (Q)
		case 1545: // fair. Stationary traffic
		case 1546: // fair. Danger of stationary traffic
		case 1547: // fair. Queuing traffic
		case 1548: // fair. Danger of queuing traffic
		case 1549: // fair. Slow traffic
		case 1550: // fair. Slow traffic expected
		case 1551: // fair. Heavy traffic
		case 1552: // fair. Heavy traffic expected
		case 1553: // fair. Traffic flowing freely
		case 1554: // fair. Traffic building up
		case 1555: // closed due to fair
		case 1556: // fair. Delays (Q)
		case 1557: // fair. Delays (Q) expected
		case 1558: // fair. Long delays (Q)
		case 1559: // closed due to parade
		case 1560: // parade. Delays (Q)
		case 1561: // parade. Delays (Q) expected
		case 1562: // parade. Long delays (Q)
		case 1563: // closed due to strike
		case 1564: // strike. Delays (Q)
		case 1565: // strike. Delays (Q) expected
		case 1566: // strike. Long delays (Q)
		case 1567: // closed due to demonstration
		case 1568: // demonstration. Delays (Q)
		case 1569: // demonstration. Delays (Q) expected
		case 1570: // demonstration. Long delays (Q)
		case 1571: // security alert. Stationary traffic
		case 1572: // security alert. Danger of stationary traffic
		case 1573: // security alert. Queuing traffic
		case 1574: // security alert. Danger of queuing traffic
		case 1575: // security alert. Slow traffic
		case 1576: // security alert. Slow traffic expected
		case 1577: // security alert. Heavy traffic
		case 1578: // security alert. Heavy traffic expected
		case 1579: // security alert. Traffic building up
		case 1580: // closed due to security alert
		case 1581: // security alert. Delays (Q)
		case 1582: // security alert. Delays (Q) expected
		case 1583: // security alert. Long delays (Q)
		case 1586: // security alert. Traffic flowing freely
		case 1590: // several major events
		case 1591: // information about major event no longer valid
		case 1592: // automobile race
		case 1593: // baseball game
		case 1594: // basketball game
		case 1595: // boat race
		case 1596: // concert
		case 1597: // hockey game
		case 1601: // delays (Q)
		case 1602: // delays up to 15 minutes
		case 1603: // delays up to 30 minutes
		case 1604: // delays up to one hour
		case 1605: // delays up to two hours
		case 1606: // delays of several hours
		case 1607: // delays (Q) expected
		case 1608: // long delays (Q)
		case 1609: // delays (Q) for heavy vehicles
		case 1610: // delays up to 15 minutes for heavy lorr(y/ies)
		case 1611: // delays up to 30 minutes for heavy lorr(y/ies)
		case 1612: // delays up to one hour for heavy lorr(y/ies)
		case 1613: // delays up to two hours for heavy lorr(y/ies)
		case 1614: // delays of several hours for heavy lorr(y/ies)
		case 1615: // service suspended (until Q)
		case 1616: // (Q) service withdrawn
		case 1617: // (Q) service(s) fully booked
		case 1618: // (Q) service(s) fully booked for heavy vehicles
		case 1619: // normal services resumed
		case 1620: // message cancelled
		case 1621: // delays up to 5 minutes
		case 1622: // delays up to 10 minutes
		case 1623: // delays up to 20 minutes
		case 1624: // delays up to 25 minutes
		case 1625: // delays up to 40 minutes
		case 1626: // delays up to 50 minutes
		case 1627: // delays up to 90 minutes
		case 1628: // delays up to three hours
		case 1629: // delays up to four hours
		case 1630: // delays up to five hours
		case 1631: // very long delays (Q)
		case 1632: // delays of uncertain duration
		case 1633: // delayed until further notice
		case 1634: // cancellations
		case 1635: // park and ride service not operating (until Q)
		case 1636: // special public transport services operating (until Q)
		case 1637: // normal services not operating (until Q)
		case 1638: // rail services not operating (until Q)
		case 1639: // bus services not operating (until Q)
		case 1640: // shuttle service operating (until Q)
		case 1641: // free shuttle service operating (until Q)
		case 1642: // delays (Q) for heavy lorr(y/ies)
		case 1643: // delays (Q) for buses
		case 1644: // (Q) service(s) fully booked for heavy lorr(y/ies)
		case 1645: // (Q) service(s) fully booked for buses
		case 1646: // next departure (Q) for heavy lorr(y/ies)
		case 1647: // next departure (Q) for buses
		case 1648: // delays cleared
		case 1649: // rapid transit service not operating (until Q)
		case 1650: // delays (Q) possible
		case 1651: // underground service not operating (until Q)
		case 1652: // cancellations expected
		case 1653: // long delays expected
		case 1654: // very long delays expected
		case 1655: // all services fully booked (until Q)
		case 1656: // next arrival (Q)
		case 1657: // rail services irregular. Delays (Q)
		case 1658: // bus services irregular. Delays (Q)
		case 1659: // underground services irregular
		case 1660: // normal public transport services resumed
		case 1661: // ferry service not operating (until Q)
		case 1662: // park and ride trip time (Q)
		case 1663: // delay expected to be cleared
		case 1664: // demonstration by vehicles
		case 1680: // delays (Q) have to be expected
		case 1681: // delays of several hours have to be expected
		case 1682: // closed ahead. Delays (Q) have to be expected
		case 1683: // roadworks. Delays (Q) have to be expected
		case 1684: // flooding. Delays (Q) have to be expected
		case 1685: // major event. Delays (Q) have to be expected
		case 1686: // strike. Delays (Q) have to be expected
		case 1687: // delays of several hours for heavy lorries have to be expected
		case 1688: // long delays have to be expected
		case 1689: // very long delays have to be expected
		case 1690: // delay forecast withdrawn
		case 1691: // message cancelled
		case 1695: // current trip time (Q)
		case 1696: // expected trip time (Q)
		case 1700: // (Q) slow moving maintenance vehicle(s)
		case 1701: // (Q) vehicle(s) on wrong carriageway
		case 1702: // dangerous vehicle warning cleared
		case 1703: // message cancelled
		case 1704: // (Q) reckless driver(s)
		case 1705: // (Q) prohibited vehicle(s) on the roadway
		case 1706: // (Q) emergency vehicles
		case 1707: // (Q) high-speed emergency vehicles
		case 1708: // high-speed chase (involving Q vehicles)
		case 1709: // spillage occurring from moving vehicle
		case 1710: // objects falling from moving vehicle
		case 1711: // emergency vehicle warning cleared
		case 1712: // road cleared
		case 1720: // rail services irregular
		case 1721: // public transport services not operating
		case 1731: // (Q) abnormal load(s), danger
		case 1732: // (Q) wide load(s), danger
		case 1733: // (Q) long load(s), danger
		case 1734: // (Q) slow vehicle(s), danger
		case 1735: // (Q) track-laying vehicle(s), danger
		case 1736: // (Q) vehicle(s) carrying hazardous materials. Danger
		case 1737: // (Q) convoy(s), danger
		case 1738: // (Q) military convoy(s), danger
		case 1739: // (Q) overheight load(s), danger
		case 1740: // abnormal load causing slow traffic. Delays (Q)
		case 1741: // convoy causing slow traffic. Delays (Q)
		case 1751: // (Q) abnormal load(s)
		case 1752: // (Q) wide load(s)
		case 1753: // (Q) long load(s)
		case 1754: // (Q) slow vehicle(s)
		case 1755: // (Q) convoy(s)
		case 1756: // abnormal load. Delays (Q)
		case 1757: // abnormal load. Delays (Q) expected
		case 1758: // abnormal load. Long delays (Q)
		case 1759: // convoy causing delays (Q)
		case 1760: // convoy. Delays (Q) expected
		case 1761: // convoy causing long delays (Q)
		case 1762: // exceptional load warning cleared
		case 1763: // message cancelled
		case 1764: // (Q) track-laying vehicle(s)
		case 1765: // (Q) vehicle(s) carrying hazardous materials
		case 1766: // (Q) military convoy(s)
		case 1767: // (Q) abnormal load(s). No overtaking
		case 1768: // Vehicles carrying hazardous materials have to stop at next safe place!
		case 1769: // hazardous load warning cleared
		case 1770: // convoy cleared
		case 1771: // warning cleared
		case 1780: // cancellations have to be expected
		case 1781: // all services fully booked (until Q)
		case 1782: // park and ride service will not be operating (until Q)
		case 1783: // normal services will not be operating (until Q)
		case 1784: // rail services will not be operating (until Q)
		case 1785: // rapid transit service will not be operating (until Q)
		case 1786: // underground service will not be operating (until Q)
		case 1787: // public transport will be on strike
		case 1788: // ferry service will not be operating (until Q)
		case 1789: // normal services expected
		case 1790: // message cancelled
		case 1801: // lane control signs not working
		case 1802: // emergency telephones not working
		case 1803: // emergency telephone number not working
		case 1804: // (Q sets of) traffic lights not working
		case 1805: // (Q sets of) traffic lights working incorrectly
		case 1806: // level crossing failure
		case 1807: // (Q sets of) traffic lights not working. Stationary traffic
		case 1808: // (Q sets of) traffic lights not working. Danger of stationary traffic
		case 1809: // (Q sets of) traffic lights not working. Queuing traffic
		case 1810: // (Q sets of) traffic lights not working. Danger of queuing traffic
		case 1811: // (Q sets of) traffic lights not working. Slow traffic
		case 1812: // (Q sets of) traffic lights not working. Slow traffic expected
		case 1813: // (Q sets of) traffic lights not working. Heavy traffic
		case 1814: // (Q sets of) traffic lights not working. Heavy traffic expected
		case 1815: // (Q sets of) traffic lights not working. Traffic flowing freely
		case 1816: // (Q sets of) traffic lights not working. Traffic building up
		case 1817: // traffic lights not working. Delays (Q)
		case 1818: // traffic lights not working. Delays (Q) expected
		case 1819: // traffic lights not working. Long delays (Q)
		case 1820: // level crossing failure. Stationary traffic
		case 1821: // level crossing failure. Danger of stationary traffic
		case 1822: // level crossing failure. Queuing traffic
		case 1823: // level crossing failure. Danger of queuing traffic
		case 1824: // level crossing failure. Slow traffic
		case 1825: // level crossing failure. Slow traffic expected
		case 1826: // level crossing failure. Heavy traffic
		case 1827: // level crossing failure. Heavy traffic expected
		case 1828: // level crossing failure. Traffic flowing freely
		case 1829: // level crossing failure. Traffic building up
		case 1830: // level crossing failure. Delays (Q)
		case 1831: // level crossing failure. Delays (Q) expected
		case 1832: // level crossing failure. Long delays (Q)
		case 1833: // electronic signs repaired
		case 1834: // emergency call facilities restored
		case 1835: // traffic signals repaired
		case 1836: // level crossing now working normally
		case 1837: // message cancelled
		case 1838: // lane control signs working incorrectly
		case 1839: // lane control signs operating
		case 1840: // variable message signs not working
		case 1841: // variable message signs working incorrectly
		case 1842: // variable message signs operating
		case 1843: // (Q sets of) ramp control signals not working
		case 1844: // (Q sets of) ramp control signals working incorrectly
		case 1845: // (Q sets of) temporary traffic lights not working
		case 1846: // (Q sets of) temporary traffic lights working incorrectly
		case 1847: // traffic signal control computer not working
		case 1848: // traffic signal timings changed
		case 1849: // tunnel ventilation not working
		case 1850: // lane control signs not working. Danger
		case 1851: // temporary width limit (Q)
		case 1852: // temporary width limit lifted
		case 1854: // traffic regulations have been changed
		case 1855: // less than 50 parking spaces available
		case 1856: // no parking information available (until Q)
		case 1857: // message cancelled
		case 1858: // Snowplough. Delays (Q)
		case 1861: // temporary height limit (Q)
		case 1862: // temporary height limit lifted
		case 1863: // (Q) automatic payment lanes not working
		case 1864: // lane control signs working incorrectly. Danger
		case 1865: // emergency telephones out of order. Extra police patrols in operation
		case 1866: // emergency telephones out of order. In emergency, wait for police patrol
		case 1867: // (Q sets of) traffic lights not working. Danger
		case 1868: // traffic lights working incorrectly. Delays (Q)
		case 1869: // traffic lights working incorrectly. Delays (Q) expected
		case 1870: // traffic lights working incorrectly. Long delays (Q)
		case 1871: // temporary axle load limit (Q)
		case 1872: // temporary gross weight limit (Q)
		case 1873: // temporary gross weight limit lifted
		case 1874: // temporary axle weight limit lifted
		case 1875: // (Q sets of) traffic lights working incorrectly. Danger
		case 1876: // temporary traffic lights not working. Delays (Q)
		case 1877: // temporary traffic lights not working. Delays (Q) expected
		case 1878: // temporary traffic lights not working. Long delays (Q)
		case 1879: // (Q sets of) temporary traffic lights not working. Danger
		case 1880: // traffic signal control computer not working. Delays (Q)
		case 1881: // temporary length limit (Q)
		case 1882: // temporary length limit lifted
		case 1883: // message cancelled
		case 1884: // traffic signal control computer not working. Delays (Q) expected
		case 1885: // traffic signal control computer not working. Long delays (Q)
		case 1886: // normal parking restrictions lifted
		case 1887: // special parking restrictions in force
		case 1888: // 10% full
		case 1889: // 20% full
		case 1890: // 30% full
		case 1891: // 40% full
		case 1892: // 50% full
		case 1893: // 60% full
		case 1894: // 70% full
		case 1895: // 80% full
		case 1896: // 90% full
		case 1897: // less than 10 parking spaces available
		case 1898: // less than 20 parking spaces available
		case 1899: // less than 30 parking spaces available
		case 1900: // less than 40 parking spaces available
		case 1901: // next departure (Q)
		case 1902: // next departure (Q) for heavy vehicles
		case 1903: // car park (Q) full
		case 1904: // all car parks (Q) full
		case 1905: // less than (Q) car parking spaces available
		case 1906: // park and ride service operating (until Q)
		case 1907: // (null event) {no event description, but location etc. given in message}
		case 1908: // switch your car radio (to Q)
		case 1909: // alarm call: important new information on this frequency follows now in normal programme
		case 1910: // alarm set: new information will be broadcast between these times in normal programme
		case 1911: // message cancelled
		case 1913: // switch your car radio (to Q)
		case 1914: // no information available (until Q)
		case 1915: // this message is for test purposes only (number Q), please ignore
		case 1916: // no information available (until Q) due to technical problems
		case 1917: // automatic toll system not working, pay manually
		case 1918: // full
		case 1920: // only a few parking spaces available
		case 1921: // (Q) parking spaces available
		case 1922: // expect car park to be full
		case 1923: // expect no parking spaces available
		case 1924: // multi story car parks full
		case 1925: // no problems to report with park and ride services
		case 1926: // no parking spaces available
		case 1927: // no parking (until Q)
		case 1928: // special parking restrictions lifted
		case 1929: // urgent information will be given (at Q) on normal programme broadcasts
		case 1930: // this TMC-service is not active (until Q)
		case 1931: // detailed information will be given (at Q) on normal programme broadcasts
		case 1932: // detailed information is provided by another TMC service
		case 1934: // no park and ride information available (until Q)
		case 1938: // park and ride information service resumed
		case 1939: // travel information telephone service availiable
		case 1940: // additional regional information is provided by another TMC service
		case 1941: // additional local information is provided by another TMC service
		case 1942: // additional public transport information is provided by another TMC service
		case 1943: // national traffic information is provided by another TMC service
		case 1944: // this service provides major road information
		case 1945: // this service provides regional travel information
		case 1946: // this service provides local travel information
		case 1947: // no detailed regional information provided by this service
		case 1948: // no detailed local information provided by this service
		case 1949: // no cross-border information provided by this service
		case 1950: // information restricted to this area
		case 1951: // no new traffic information available (until Q)
		case 1952: // no public transport information available
		case 1953: // this TMC-service is being suspended (at Q)
		case 1954: // active TMC-service will resume (at Q)
		case 1955: // reference to audio programmes no longer valid
		case 1956: // reference to other TMC services no longer valid
		case 1957: // previous announcement about this or other TMC services no longer valid
		case 1961: // allow emergency vehicles to pass in the carpool lane
		case 1962: // carpool lane available for all vehicles
		case 1963: // police directing traffic via the carpool lane
		case 1964: // rail information service not available
		case 1965: // rail information service resumed
		case 1966: // rapid transit information service not available
		case 1967: // rapid transit information service resumed
		case 1971: // police directing traffic
		case 1972: // buslane available for all vehicles
		case 1973: // police directing traffic via the buslane
		case 1974: // allow emergency vehicles to pass
		case 1975: // overtaking prohibited for heavy vehicles (over Q)
		case 1976: // overtaking prohibited
		case 1977: // allow emergency vehicles to pass in the heavy vehicle lane
		case 1978: // heavy vehicle lane available for all vehicles
		case 1979: // police directing traffic via the heavy vehicle lane
		case 1980: // overtaking prohibited for heavy lorries (over Q)
		case 1981: // drivers of heavy lorries (over Q) are recommended to stop at next safe place
		case 1982: // buslane closed
		case 1983: // power failure
		case 1985: // overtaking restriction lifted
		case 1986: // Low Emission Zone restriction in force
		case 1991: // danger of waiting vehicles on roadway
		case 1993: // number of parking spaces decreasing
		case 1994: // number of parking spaces constant
		case 1995: // number of parking spaces increasing
		case 1998: // dangerous situation on exit slip road
		case 1999: // dangerous situation on entry slip road
		case 2000: // closed due to smog alert (until Q)
		case 2006: // closed for vehicles with less than three occupants  {not valid for lorries}
		case 2007: // closed for vehicles with only one occupant {not valid for lorries}
		case 2013: // service area busy
		case 2021: // service not operating, substitute service available
		case 2022: // public transport strike
		case 2042: // ice build-up on cable structure
		case 2043: // road salted
		case 2044: // danger of snow patches
		case 2045: // snow patches
		case 2046: // Convoy service required due to bad weather
		default:
			break;
		}
	}
}

array_map("array_unique", $roles);

$primary = find_place($cid, $tabcd, $lcd);
if($primary['class'] == 'P')
	$secondary = find_offsets($cid, $tabcd, $lcd, $ext, $dir);
else
	$secondary = array($primary);

$opquery = "(";
if($primary['class'] == 'P')
{
	foreach($secondary as $location)
	{
		$opquery .= "relation[\"type\"=\"tmc:point\"][\"table\"=\"$cid:$tabcd\"][\"lcd\"=\"{$location['lcd']}\"];";
		if(array_key_exists('pos_off_lcd', $location) && array_key_exists($location['pos_off_lcd'], $secondary))
			$opquery .= "relation[\"type\"=\"tmc:link\"][\"table\"=\"$cid:$tabcd\"][\"neg_lcd\"=\"{$location['lcd']}\"][\"pos_lcd\"=\"{$location['pos_off_lcd']}\"];";
		if(array_key_exists('neg_off_lcd', $location) && array_key_exists($location['neg_off_lcd'], $secondary))
			$opquery .= "relation[\"type\"=\"tmc:link\"][\"table\"=\"$cid:$tabcd\"][\"pos_lcd\"=\"{$location['lcd']}\"][\"neg_lcd\"=\"{$location['neg_off_lcd']}\"];";
	}
}
else if($primary['class'] == 'A')
{
	$opquery .= "relation[\"type\"=\"tmc:area\"][\"table\"=\"$cid:$tabcd\"][\"lcd\"=\"{$location['lcd']}\"];";
}
$opquery .= ");";
$opquery = "(${opquery}rel(r););";
$opquery = "(${opquery}>;);out meta;";

$opurl = "http://overpass-api.de/api/interpreter?data=" . rawurlencode($opquery);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $opurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$opdata = curl_exec($ch);
curl_close($ch);

if($opdata === false)
	$opdata = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<osm version=\"0.6\" generator=\"Overpass API\">\n</osm>";

$osmxml = new DOMDocument;
$osmxml->formatOutput = false;
$osmxml->loadXML($opdata);
$osmxp = new DOMXPath($osmxml);

$nodes = array();
$osmnodes = $osmxp->query("/osm/node");
foreach($osmnodes as $osmnode)
	$nodes[$osmnode->getAttribute('id')] = $osmnode;

$ways = array();
$osmways = $osmxp->query("/osm/way");
foreach($osmways as $osmway)
	$ways[$osmway->getAttribute('id')] = $osmway;

$rels = array();
$osmrels = $osmxp->query("/osm/relation");
foreach($osmrels as $osmrel)
	$rels[$osmrel->getAttribute('id')] = $osmrel;

$features = array();
foreach($rels as $rel => $osmrel)
{
	$relprops = array('relation' => $rel);
	$reltags = $osmxp->query("tag", $osmrel);
	foreach($reltags as $reltag)
		$relprops[$reltag->getAttribute('k')] = $reltag->getAttribute('v');

	if(!array_key_exists('type', $relprops))
		continue;

	if(substr($relprops['type'], 0, 4) != 'tmc:')
		continue;

	$members = $osmxp->query("member", $osmrel);
	foreach($members as $member)
	{
		$id = $member->getAttribute('ref');
		$type = $member->getAttribute('type');
		$role = $member->getAttribute('role');
		$props = array('id' => $id, 'member' => $type, 'role' => $role);

		if(!preg_match('/(positive|negative|both|):?(entry|exit|ramp|parking|fuel|restaurant|)/', $role, $matches))
			continue;

		//echo "<!--"; print_r($matches); echo "-->\n";

		if(($message['directions'] == 1) && ($matches[1] == ($message['direction'] ? 'negative' : 'positive')))
			continue;

		if(!array_key_exists($matches[2], $roles))
			continue;

		if(!count($roles[$matches[2]]))
			continue;

		$props['message'] = $roles[$matches[2]];

		if($type == 'node')
		{
			$geom = array('type' => 'Point', 'coordinates' => array($nodes[$id]->getAttribute('lon'), $nodes[$id]->getAttribute('lat')));
		}
		else if($type == 'way')
		{
			$wns = $osmxp->query('nd', $ways[$id]);
			$coords = array();
			foreach($wns as $wn)
			{
				$nd = $wn->getAttribute('ref');
				$coords[] = array($nodes[$nd]->getAttribute('lon'), $nodes[$nd]->getAttribute('lat'));
			}
			if(($coords[0][0] == $coords[count($coords) - 1][0]) && ($coords[0][1] == $coords[count($coords) - 1][1]))
				$geom = array('type' => 'Polygon', 'coordinates' => array($coords));
			else
				$geom = array('type' => 'LineString', 'coordinates' => $coords);
		}

		$features[] = array('type' => 'Feature', 'properties' => array_merge($props, $relprops), 'geometry' => $geom);
	}
}

$featcoll = array('type' => 'FeatureCollection', 'features' => $features);
$osmjson = json_string($featcoll);

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="rds.css"/>
<title>TMC message viewer</title>
<script src="http://www.openlayers.org/api/OpenLayers.js"></script>
<script src="http://www.openstreetmap.org/openlayers/OpenStreetMap.js"></script>
<script src="tmcmsgmap.js"></script>
<script type="text/javascript">
osmdata = <?php echo $osmjson; ?>;
</script>
</head>
<body onload="init();">
<div id="map" style="position: fixed; top: 12px; bottom: 12px; left: 480px; right: 12px"></div>
<div id="list" style="position: absolute; left: 12px; top: 12px; bottom: 12px; width: 456px; overflow: auto">
<h1>TMC message viewer</h1>
<?php
echo "<h3>Raw message data</h3>\n";
echo "<pre>$raw</pre>\n";

echo "<h3>Interpreted message data</h3>\n";
echo "<ul>\n";

echo "<li>Primary location: " . location_link($lcd) . " - " . array_desc($primary) . "</li>\n";
echo "<li>Extent: {$message['extent']}</li>\n";
echo "<li>Direction: " . ($message['direction'] ? "negative" : "positive") . "</li>\n";

if(($primary['class'] == 'P') && ($ext > 0))
{
	echo "<li>Affected locations:<ul>\n";
	foreach($secondary as $key => $value)
		echo "<li>" . location_link($key) . " - " . array_desc($value) . "</li>\n";
	echo "</ul></li>\n";
}

echo "<li>Affected directions: {$message['directions']}</li>\n";
echo "<li>Urgency: {$urgencies[$message['urgency']]}</li>\n";

if(array_key_exists('duration', $message))
	echo "<li>Duration: " . decode_duration($message['duration'], $message['durtype'], $message['nature']) . "</li>\n";
if(array_key_exists('start', $message))
	echo "<li>Start time: " . decode_time($message['start'], $time) . "</li>\n";
if(array_key_exists('stop', $message))
	echo "<li>Stop time: " . decode_time($message['stop'], $time) . "</li>\n";

echo "<li>Information blocks:<ul>\n";
foreach($message['iblocks'] as $iblock)
{
	echo "<li>Events / quantifier:<ul>\n";
	foreach($iblock['events'] as $event)
	{
		echo "<li>" . $event['code'] . " - ";
		if($event['reference'] != '')
			echo $event['reference'] . ": ";
		if(array_key_exists('quant', $event))
			echo preg_replace('/\(([^\)]*)Q([^\)]*)\)/', '${1}' . find_quantifier($event['quantifier'], $event['quant']) . $units[$event['quantifier']] . '${2}', $event['text']);
		else
			echo trim(preg_replace('/\([^\)]*Q[^\)]*\)/', '', $event['text']));
		echo "</li>\n";
	}
	echo "</ul></li>\n";
}
echo "</ul></li>\n";

if(count($message['supps']))
{
	echo "<li>Supplements:<ul>\n";
	foreach($message['supps'] as $supp)
		echo "<li>{$supp['code']} - {$supp['text']}</li>\n";
	echo "</ul></li>\n";
}

if(count($message['diversions']))
{
	echo "<li>Diversions:<ul>\n";
	foreach($message['diversions'] as $diversion)
	{
		echo "<li>";
		if(array_key_exists('destinations', $diversion))
			echo "Diversion to " . implode(", ", array_map("location_link", $diversion['destinations']));
		else
			echo "General diversion";
		echo " via " . implode(", ", array_map("location_link", $diversion['route'])) . "</li>\n";
	}
	echo "</ul></li>\n";
}

if(array_key_exists('cross', $message))
	echo "<li>Cross-linked location: " . location_link($message['cross']) . "</li>\n";

echo "</ul>\n";

echo "<h3>OSM linked data</h3>\n";
echo "<ul>\n";
echo "<li><a href=\"http://overpass-turbo.eu/map.html?Q=" . rawurlencode($opquery) . "\">Show as Overpass-Turbo map</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=xml&amp;data=" . rawurlencode($opquery) . "\">Convert to XML</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=mapql&amp;data=" . rawurlencode($opquery) . "\">Convert to pretty Overpass QL</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=compact&amp;data=" . rawurlencode($opquery) . "\">Convert to compact Overpass QL</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=ol_fixed&amp;data=" . rawurlencode($opquery) . "\">Show as auto-centered overlay</a></li>\n";
echo "<li><a href=\"http://www.overpass-api.de/api/convert?target=ol_bbox&amp;data=" . rawurlencode($opquery) . "\">Show as slippy overlay</a></li>\n";
echo "</ul>\n";
?>
</div>
</body>
</html>
