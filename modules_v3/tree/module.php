<?php
// Copyright (C) 2014 webtrees development team
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA

/**
 * Class tree_WT_Module
 * Tip : you could change the number of generations loaded before ajax calls both in individual page and in treeview page to optimize speed and server load
 */
class tree_WT_Module extends WT_Module implements WT_Module_Tab {
	var $headers; // CSS and script to include in the top of <head> section, before theme’s CSS
	var $js; // the TreeViewHandler javascript

	/** {@inheritdoc} */
	public function getTitle() {
		return /* I18N: Name of a module */ WT_I18N::translate('Interactive tree');
	}

	/** {@inheritdoc} */
	public function getDescription() {
		return /* I18N: Description of the “Interactive tree” module */ WT_I18N::translate('An interactive tree, showing all the ancestors and descendants of an individual.');
	}

	/** {@inheritdoc} */
	public function defaultTabOrder() {
		return 68;
	}

	/** {@inheritdoc} */
	public function getTabContent() {
		global $controller;

		require_once WT_MODULES_DIR . $this->getName() . '/class_treeview.php';
		$tv = new TreeView('tvTab');
		list($html, $js) = $tv->drawViewport($controller->record, 3);
		return
			'<script src="' . $this->js() . '"></script>' .
			'<script>' . $js . '</script>' .
			$html;
	}

	/** {@inheritdoc} */
	public function hasTabContent() {
		global $SEARCH_SPIDER;

		return !$SEARCH_SPIDER;
	}

	/** {@inheritdoc} */
	public function isGrayedOut() {
		return false;
	}

	/** {@inheritdoc} */
	public function canLoadAjax() {
		return true;
	}

	/** {@inheritdoc} */
	public function getPreLoadContent() {
		// We cannot use jQuery("head").append(<link rel="stylesheet" ...as jQuery is not loaded at this time
		return
			'<script>
			if (document.createStyleSheet) {
				document.createStyleSheet("' . $this->css() . '"); // For Internet Explorer
			} else {
				var newSheet=document.createElement("link");
    		newSheet.setAttribute("rel","stylesheet");
    		newSheet.setAttribute("type","text/css");
   			newSheet.setAttribute("href","' . $this->css() . '");
		    document.getElementsByTagName("head")[0].appendChild(newSheet);
			}
			</script>';
	}

	/** {@inheritdoc} */
	public function modAction($mod_action) {
		require_once WT_MODULES_DIR . $this->getName() . '/class_treeview.php';
		switch ($mod_action) {
		case 'treeview':
			global $controller;
			$controller = new WT_Controller_Chart;
			$tv = new TreeView('tv');
			ob_start();

			$person = $controller->getSignificantIndividual();

			list($html, $js) = $tv->drawViewport($person, 4);

			$controller
				->setPageTitle(WT_I18N::translate('Interactive tree of %s', $person->getFullName()))
				->pageHeader()
				->addExternalJavascript($this->js())
				->addInlineJavascript($js)
				->addInlineJavascript('
					if (document.createStyleSheet) {
						document.createStyleSheet("' . $this->css() . '"); // For Internet Explorer
					} else {
						jQuery("head").append(\'<link rel="stylesheet" type="text/css" href="' . $this->css() . '">\');
					}
				');
			echo $html;
			break;

		case 'getDetails':
			Zend_Session::writeClose();
			header('Content-Type: text/html; charset=UTF-8');
			$pid = WT_Filter::get('pid', WT_REGEX_XREF);
			$i = WT_Filter::get('instance');
			$tv = new TreeView($i);
			$individual = WT_Individual::getInstance($pid);
			if ($individual) {
				echo $tv->getDetails($individual);
			}
			break;

		case 'getPersons':
			Zend_Session::writeClose();
			header('Content-Type: text/html; charset=UTF-8');
			$q = WT_Filter::get('q');
			$i = WT_Filter::get('instance');
			$tv = new TreeView($i);
			echo $tv->getPersons($q);
			break;

		default:
			http_response_code(404);
			break;
		}
	}

	/**
	 * @return string
	 */
	public function css() {
		return WT_STATIC_URL . WT_MODULES_DIR . $this->getName() . '/css/treeview.css';
	}

	/**
	 * @return string
	 */
	public function js() {
		return WT_STATIC_URL . WT_MODULES_DIR . $this->getName() . '/js/treeview.js';
	}
}
