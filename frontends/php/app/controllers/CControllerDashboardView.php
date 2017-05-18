<?php
/*
** Zabbix
** Copyright (C) 2001-2017 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/../../include/blocks.inc.php';

class CControllerDashboardView extends CController {

	private $widget_config;

	public function __construct() {
		parent::__construct();

		$this->widget_config = new CWidgetConfig();
	}

	protected function init() {
		$this->disableSIDValidation();
	}

	protected function checkInput() {
		$fields = [
			'fullscreen' =>		'in 0,1',
			'dashboardid' =>	'db dashboard.dashboardid'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions() {
		if ($this->getUserType() < USER_TYPE_ZABBIX_USER) {
			return false;
		}

		if ($this->hasInput('dashboardid')) {
			$dashboards = API::Dashboard()->get([
				'output' => [],
				'dashboardids' => $this->getInput('dashboardid')
			]);

			if (!$dashboards) {
				return false;
			}
		}

		return true;
	}

	protected function doAction() {
		$dashboard = $this->getDashboard();

		if ($dashboard === null) {
			$url = (new CUrl('zabbix.php'))->setArgument('action', 'dashboard.list');
			$this->setResponse((new CControllerResponseRedirect($url->getUrl())));
			return;
		}

		$data = [
			'dashboard' => $dashboard,
			'fullscreen' => $this->getInput('fullscreen', '0'),
			'filter_enabled' => CProfile::get('web.dashconf.filter.enable', 0),
			'grid_widgets' => $this->getWidgets($dashboard['widgets'])
		];

		$response = new CControllerResponseData($data);
		$response->setTitle(_('Dashboard'));
		$this->setResponse($response);
	}

	/**
	 * Get dashboard data from API
	 *
	 * @return array|null
	 */
	private function getDashboard() {
		$dashboardid = $this->getInput('dashboardid', CProfile::get('web.dashbrd.dashboardid', 0));

		if ($dashboardid == 0 && CProfile::get('web.dashbrd.list_was_opened') != 1) {
			$dashboardid = DASHBOARD_DEFAULT_ID;
		}

		$dashboard = null;

		if ($dashboardid != 0) {
			$dashboards = API::Dashboard()->get([
				'output' => ['dashboardid', 'name'],
				'selectWidgets' => ['widgetid', 'type', 'name', 'row', 'col', 'height', 'width', 'fields'],
				'dashboardids' => $dashboardid
			]);

			if ($dashboards) {
				$dashboard = $dashboards[0];

				CProfile::update('web.dashbrd.dashboardid', $dashboardid, PROFILE_TYPE_ID);
			}
		}

		return $dashboard;
	}

	/**
	 * Get widgets for dashboard
	 *
	 * @return array
	 */
	private function getWidgets($widgets) {
		$grid_widgets = [];
		$widget_names = $this->widget_config->getKnownWidgetTypesWNames();
		// TODO VM: (?) WIDGET_DISCOVERY_STATUS and WIDGET_ZABBIX_STATUS are displayed only under specidic conditions,
		// but we currently have these widgets in default dashboard. Should these conditions be be managed by frontend, or API?
		// Currently these conditions are not managed by any of them.

		foreach ($widgets as  $widget) {
			$widgetid = (int) $widget['widgetid'];
			$default_rf_rate = $this->widget_config->getDefaultRfRate($widget['type']);

			$grid_widgets[$widgetid]['widgetid'] = $widgetid;
			$grid_widgets[$widgetid]['type'] = $widget['type'];
			$grid_widgets[$widgetid]['header'] = (strlen($widget['name']) != 0) ? $widget['name'] : $widget_names[$widget['type']];
			$grid_widgets[$widgetid]['triggerrs'] = $this->widget_config->getTriggers($widget['type']);
			// TODO VM: widget headers are not affeced by name from database, because it is rewritten by specific widget's API call
			$grid_widgets[$widgetid]['pos'] = [];
			$grid_widgets[$widgetid]['pos']['row'] = (int) $widget['row'];
			$grid_widgets[$widgetid]['pos']['col'] = (int) $widget['col'];
			$grid_widgets[$widgetid]['pos']['height'] = (int) $widget['height'];
			$grid_widgets[$widgetid]['pos']['width'] = (int) $widget['width'];
			$grid_widgets[$widgetid]['rf_rate'] = (int) CProfile::get('web.dashbrd.widget.'.$widgetid.'.rf_rate', $default_rf_rate);
			// TODO VM: (?) update refresh rate to take into account dashboard id
			//			(1) Adding dashboard ID will limit reusage of dashboard.grid.js for pages without dashboard ID's
			//			(2) Each widget has unique ID across all dashboards, so it will still work
			//			(3) Leaving identification only be widget ID, it will be harder to manage, when deleating dashboards.

			// 'type' always should be in fields array
			$widget['fields'] = $this->convertWidgetFields($widget['fields']);
			$widget['fields']['type'] = $widget['type'];
			$grid_widgets[$widgetid]['fields'] = $widget['fields'];
		}
		// TODO VM: delete refresh rate from all user profiles when deleting widget
		// TODO VM: delete refresh rate from all user profiles when deleting dashboard
		return $grid_widgets;
	}

	/**
	 * Converts fields, received from API to key/value format
	 * @param array $fields - fields as received from API
	 * @return array
	 */
	private function convertWidgetFields($fields) {
		$ret = [];
		foreach ($fields as $field) {
			$field_key = $this->widget_config->getApiFieldKey($field['type']);
			$ret[$field['name']] = $field[$field_key];
		}
		return $ret;
	}
}
