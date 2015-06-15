<?php
/*
** Zabbix
** Copyright (C) 2001-2015 Zabbix SIA
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


require_once dirname(__FILE__).'/js/configuration.host.edit.js.php';

$widget = (new CWidget())
	->setTitle(_('Hosts'))
	->addItem(get_header_host_table('', $data['hostid']));

$divTabs = new CTabView();
if (!hasRequest('form_refresh')) {
	$divTabs->setSelected(0);
}

$frmHost = new CForm();
$frmHost->setName('web.hosts.host.php.');
$frmHost->setAttribute('id', 'hostForm');

$frmHost->addVar('form', $data['form']);
$frmHost->addVar('clear_templates', $data['clear_templates']);
$frmHost->addVar('tls_accept', $data['tls_accept']);
$frmHost->addVar('flags', $data['flags']);

if ($data['hostid'] != 0) {
	$frmHost->addVar('hostid', $data['hostid']);
}
if ($data['clone_hostid'] != 0) {
	$frmHost->addVar('clone_hostid', $data['clone_hostid']);
}
if ($data['groupid'] != 0) {
	$frmHost->addVar('groupid', $data['groupid']);
}

$hostList = new CFormList('hostlist');

// LLD rule link
if ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED) {
	$hostList->addRow(
		_('Discovered by'),
		(new CLink($data['discoveryRule']['name'], 'host_prototypes.php?parent_discoveryid='.$data['discoveryRule']['itemid']))
			->addClass('highlight')
			->addClass('underline')
			->addClass('weight_normal')
	);
}

$host_input = new CTextBox(
	'host', $data['host'], ZBX_TEXTBOX_STANDARD_SIZE, ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED), 128
);
$host_input->setAttribute('autofocus', 'autofocus');
$hostList->addRow(_('Host name'), $host_input);

$hostList->addRow(_('Visible name'),
	new CTextBox('visiblename', $data['visiblename'], ZBX_TEXTBOX_STANDARD_SIZE, ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED), 128)
);

if ($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED) {
	// groups for normal hosts
	$groupsTB = new CTweenBox($frmHost, 'groups', $data['groups'], 10);

	foreach ($data['groupsAll'] as $group) {
		if (in_array($group['groupid'], $data['groups'])) {
			$groupsTB->addItem($group['groupid'], $group['name'], null,
				array_key_exists($group['groupid'], $data['groupsAllowed'])
			);
		}
		elseif (array_key_exists($group['groupid'], $data['groupsAllowed'])) {
			$groupsTB->addItem($group['groupid'], $group['name']);
		}
	}

	$hostList->addRow(_('Groups'), $groupsTB->get(_('In groups'), _('Other groups')));

	$newgroupTB = new CTextBox('newgroup', $data['newgroup'], ZBX_TEXTBOX_SMALL_SIZE);
	$newgroupTB->setAttribute('maxlength', 64);
	$tmp_label = _('New group');
	if (CWebUser::$data['type'] != USER_TYPE_SUPER_ADMIN) {
		$tmp_label .= ' '._('(Only super admins can create groups)');
		$newgroupTB->setReadonly(true);
	}
	$hostList->addRow(new CLabel($tmp_label, 'newgroup'), $newgroupTB, null, null, ZBX_STYLE_TABLE_FORMS_TR_NEW);
}
else {
	// groups for discovered hosts
	$groupBox = new CListBox(null, null, 10);
	$groupBox->setEnabled(false);
	foreach ($data['groupsAll'] as $group) {
		if (in_array($group['groupid'], $data['groups'])) {
			$groupBox->addItem($group['groupid'], $group['name'], null,
				array_key_exists($group['groupid'], $data['groupsAllowed'])
			);
		}
	}
	$hostList->addRow(_('Groups'), $groupBox);
	$hostList->addVar('groups', $data['groups']);
}

// interfaces for normal hosts
if ($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED) {
	zbx_add_post_js($data['interfaces']
		? 'hostInterfacesManager.add('.CJs::encodeJson($data['interfaces']).');'
		: 'hostInterfacesManager.addNew("agent");');

	// Zabbix agent interfaces
	$ifTab = (new CTable())
		->setId('agentInterfaces')
		->setHeader([
			new CColHeader(),
			new CColHeader(_('IP address')),
			new CColHeader(_('DNS name')),
			new CColHeader(_('Connect to')),
			new CColHeader(_('Port')),
			(new CColHeader(_('Default')))->setColSpan(2)
		])
		->addRow((new CRow([
			(new CCol(
				(new CButton('addAgentInterface', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)
			))->setColSpan(7)
		]))->setId('agentInterfacesFooter'));

	$hostList->addRow(_('Agent interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'agent')
	);

	// SNMP interfaces
	$ifTab = (new CTable())
		->setId('SNMPInterfaces')
		->addRow((new CRow([
			(new CCol(
				(new CButton('addSNMPInterface', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)
			))->setColSpan(7)
		]))->setId('SNMPInterfacesFooter'));

	$hostList->addRow(_('SNMP interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'snmp')
	);

	// JMX interfaces
	$ifTab = (new CTable())
		->setId('JMXInterfaces')
		->addRow((new CRow([
			(new CCol(
				(new CButton('addJMXInterface', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)
			))->setColSpan(7)
		]))->setId('JMXInterfacesFooter'));

	$hostList->addRow(_('JMX interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'jmx')
	);

	// IPMI interfaces
	$ifTab = (new CTable())
		->setId('IPMIInterfaces')
		->addRow((new CRow([
			(new CCol(
				(new CButton('addIPMIInterface', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)
			))->setColSpan(7)
		]))->setId('IPMIInterfacesFooter'));

	$hostList->addRow(_('IPMI interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'ipmi')
	);
}
// interfaces for discovered hosts
else {
	$existingInterfaceTypes = [];
	foreach ($data['interfaces'] as $interface) {
		$existingInterfaceTypes[$interface['type']] = true;
	}
	zbx_add_post_js('hostInterfacesManager.add('.CJs::encodeJson($data['interfaces']).');');
	zbx_add_post_js('hostInterfacesManager.disable();');

	$hostList->addVar('interfaces', $data['interfaces']);

	// Zabbix agent interfaces
	$ifTab = (new CTable())
		->setId('agentInterfaces')
		->setHeader([
			new CColHeader(),
			new CColHeader(_('IP address')),
			new CColHeader(_('DNS name')),
			new CColHeader(_('Connect to')),
			new CColHeader(_('Port')),
			(new CColHeader(_('Default')))->setColSpan(2)
		]);

	$row = (new CRow())->setId('agentInterfacesFooter');
	if (!array_key_exists(INTERFACE_TYPE_AGENT, $existingInterfaceTypes)) {
		$row->addItem(new CCol());
		$row->addItem((new CCol(_('No agent interfaces found.')))->setColSpan(6));
	}
	$ifTab->addRow($row);

	$hostList->addRow(_('Agent interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'agent')
	);

	// SNMP interfaces
	$ifTab = (new CTable())->setId('SNMPInterfaces');

	$row = (new CRow())->setId('SNMPInterfacesFooter');
	if (!array_key_exists(INTERFACE_TYPE_SNMP, $existingInterfaceTypes)) {
		$row->addItem(new CCol());
		$row->addItem((new CCol(_('No SNMP interfaces found.')))->setColSpan(6));
	}
	$ifTab->addRow($row);

	$hostList->addRow(_('SNMP interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'snmp')
	);

	// JMX interfaces
	$ifTab = (new CTable())->setId('JMXInterfaces');

	$row = (new CRow())->setId('JMXInterfacesFooter');
	if (!array_key_exists(INTERFACE_TYPE_JMX, $existingInterfaceTypes)) {
		$row->addItem(new CCol());
		$row->addItem((new CCol(_('No JMX interfaces found.')))->setColSpan(6));
	}
	$ifTab->addRow($row);

	$hostList->addRow(_('JMX interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'jmx')
	);

	// IPMI interfaces
	$ifTab = (new CTable())->setId('IPMIInterfaces');

	$row = (new CRow())->setId('IPMIInterfacesFooter');
	if (!array_key_exists(INTERFACE_TYPE_IPMI, $existingInterfaceTypes)) {
		$row->addItem(new CCol());
		$row->addItem((new CCol(_('No IPMI interfaces found.')))->setColSpan(6));
	}
	$ifTab->addRow($row);

	$hostList->addRow(_('IPMI interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'ipmi')
	);
}

$hostList->addRow(_('Description'), new CTextArea('description', $data['description']));

// Proxy
if ($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED) {
	$proxy = new CComboBox('proxy_hostid', $data['proxy_hostid'], null, [0 => _('(no proxy)')] + $data['proxies']);
	$proxy->setEnabled($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED);
}
else {
	$proxy = new CTextBox(null, $data['proxy_hostid'] != 0 ? $data['proxies'][$data['proxy_hostid']] : _('(no proxy)'), null, true);
	$hostList->addVar('proxy_hostid', $data['proxy_hostid']);
}
$hostList->addRow(_('Monitored by proxy'), $proxy);

$hostList->addRow(_('Enabled'),
	(new CCheckBox('status', HOST_STATUS_MONITORED))->setChecked($data['status'] == HOST_STATUS_MONITORED)
);

if ($data['clone_hostid'] != 0) {
	// host applications
	$hostApps = API::Application()->get([
		'output' => ['name'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false,
		'preservekeys' => true
	]);

	if ($hostApps) {
		$applicationsList = [];
		foreach ($hostApps as $hostAppId => $hostApp) {
			$applicationsList[$hostAppId] = $hostApp['name'];
		}
		order_result($applicationsList);

		$listBox = new CListBox('applications', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($applicationsList);
		$hostList->addRow(_('Applications'), $listBox);
	}

	// host items
	$hostItems = API::Item()->get([
		'output' => ['itemid', 'hostid', 'key_', 'name'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false,
		'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL]
	]);

	if ($hostItems) {
		$hostItems = CMacrosResolverHelper::resolveItemNames($hostItems);

		$itemsList = [];
		foreach ($hostItems as $hostItem) {
			$itemsList[$hostItem['itemid']] = $hostItem['name_expanded'];
		}
		order_result($itemsList);

		$listBox = new CListBox('items', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($itemsList);
		$hostList->addRow(_('Items'), $listBox);
	}

	// host triggers
	$hostTriggers = API::Trigger()->get([
		'output' => ['triggerid', 'description'],
		'selectItems' => ['type'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false,
		'filter' => ['flags' => [ZBX_FLAG_DISCOVERY_NORMAL]]
	]);

	if ($hostTriggers) {
		$triggersList = [];

		foreach ($hostTriggers as $hostTrigger) {
			if (httpItemExists($hostTrigger['items'])) {
				continue;
			}
			$triggersList[$hostTrigger['triggerid']] = $hostTrigger['description'];
		}

		if ($triggersList) {
			order_result($triggersList);

			$listBox = new CListBox('triggers', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($triggersList);
			$hostList->addRow(_('Triggers'), $listBox);
		}
	}

	// host graphs
	$hostGraphs = API::Graph()->get([
		'output' => ['graphid', 'name'],
		'selectHosts' => ['hostid'],
		'selectItems' => ['type'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false,
		'filter' => ['flags' => [ZBX_FLAG_DISCOVERY_NORMAL]]
	]);

	if ($hostGraphs) {
		$graphsList = [];
		foreach ($hostGraphs as $hostGraph) {
			if (count($hostGraph['hosts']) > 1) {
				continue;
			}
			if (httpItemExists($hostGraph['items'])) {
				continue;
			}
			$graphsList[$hostGraph['graphid']] = $hostGraph['name'];
		}

		if ($graphsList) {
			order_result($graphsList);

			$listBox = new CListBox('graphs', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($graphsList);
			$hostList->addRow(_('Graphs'), $listBox);
		}
	}

	// discovery rules
	$hostDiscoveryRuleIds = [];

	$hostDiscoveryRules = API::DiscoveryRule()->get([
		'output' => ['itemid', 'hostid', 'key_', 'name'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false
	]);

	if ($hostDiscoveryRules) {
		$hostDiscoveryRules = CMacrosResolverHelper::resolveItemNames($hostDiscoveryRules);

		$discoveryRuleList = [];
		foreach ($hostDiscoveryRules as $discoveryRule) {
			$discoveryRuleList[$discoveryRule['itemid']] = $discoveryRule['name_expanded'];
		}
		order_result($discoveryRuleList);
		$hostDiscoveryRuleIds = array_keys($discoveryRuleList);

		$listBox = new CListBox('discoveryRules', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($discoveryRuleList);
		$hostList->addRow(_('Discovery rules'), $listBox);
	}

	// item prototypes
	$hostItemPrototypes = API::ItemPrototype()->get([
		'output' => ['itemid', 'hostid', 'key_', 'name'],
		'hostids' => [$data['clone_hostid']],
		'discoveryids' => $hostDiscoveryRuleIds,
		'inherited' => false
	]);

	if ($hostItemPrototypes) {
		$hostItemPrototypes = CMacrosResolverHelper::resolveItemNames($hostItemPrototypes);

		$prototypeList = [];
		foreach ($hostItemPrototypes as $itemPrototype) {
			$prototypeList[$itemPrototype['itemid']] = $itemPrototype['name_expanded'];
		}
		order_result($prototypeList);

		$listBox = new CListBox('itemsPrototypes', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($prototypeList);
		$hostList->addRow(_('Item prototypes'), $listBox);
	}

	// Trigger prototypes
	$hostTriggerPrototypes = API::TriggerPrototype()->get([
		'output' => ['triggerid', 'description'],
		'selectItems' => ['type'],
		'hostids' => [$data['clone_hostid']],
		'discoveryids' => $hostDiscoveryRuleIds,
		'inherited' => false
	]);

	if ($hostTriggerPrototypes) {
		$prototypeList = [];
		foreach ($hostTriggerPrototypes as $triggerPrototype) {
			// skip trigger prototypes with web items
			if (httpItemExists($triggerPrototype['items'])) {
				continue;
			}
			$prototypeList[$triggerPrototype['triggerid']] = $triggerPrototype['description'];
		}

		if ($prototypeList) {
			order_result($prototypeList);

			$listBox = new CListBox('triggerprototypes', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($prototypeList);
			$hostList->addRow(_('Trigger prototypes'), $listBox);
		}
	}

	// Graph prototypes
	$hostGraphPrototypes = API::GraphPrototype()->get([
		'output' => ['graphid', 'name'],
		'selectHosts' => ['hostid'],
		'hostids' => [$data['clone_hostid']],
		'discoveryids' => $hostDiscoveryRuleIds,
		'inherited' => false
	]);

	if ($hostGraphPrototypes) {
		$prototypeList = [];
		foreach ($hostGraphPrototypes as $graphPrototype) {
			if (count($graphPrototype['hosts']) == 1) {
				$prototypeList[$graphPrototype['graphid']] = $graphPrototype['name'];
			}
		}
		order_result($prototypeList);

		$listBox = new CListBox('graphPrototypes', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($prototypeList);
		$hostList->addRow(_('Graph prototypes'), $listBox);
	}

	// host prototypes
	$hostPrototypes = API::HostPrototype()->get([
		'output' => ['hostid', 'name'],
		'discoveryids' => $hostDiscoveryRuleIds,
		'inherited' => false
	]);

	if ($hostPrototypes) {
		$prototypeList = [];
		foreach ($hostPrototypes as $hostPrototype) {
			$prototypeList[$hostPrototype['hostid']] = $hostPrototype['name'];
		}
		order_result($prototypeList);

		$listBox = new CListBox('hostPrototypes', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($prototypeList);
		$hostList->addRow(_('Host prototypes'), $listBox);
	}

	// web scenarios
	$httpTests = API::HttpTest()->get([
		'output' => ['httptestid', 'name'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false
	]);

	if ($httpTests) {
		$httpTestList = [];

		foreach ($httpTests as $httpTest) {
			$httpTestList[$httpTest['httptestid']] = $httpTest['name'];
		}

		order_result($httpTestList);

		$listBox = new CListBox('httpTests', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($httpTestList);
		$hostList->addRow(_('Web scenarios'), $listBox);
	}
}

$divTabs->addTab('hostTab', _('Host'), $hostList);

// templates
$tmplList = new CFormList();

// create linked template table
$linkedTemplateTable = (new CTable())
	->setNoDataMessage(_('No templates linked.'))
	->addClass('formElementTable')
	->setId('linkedTemplateTable');

// templates for normal hosts
if ($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED) {
	$linkedTemplateTable->setHeader([_('Name'), _('Action')]);
	$ignoredTemplates = [];

	foreach ($data['linked_templates'] as $template) {
		$tmplList->addVar('templates[]', $template['templateid']);
		$templateLink = (new CLink($template['name'], 'templates.php?form=update&templateid='.$template['templateid']))
			->setTarget('_blank');

		$unlinkButton = (new CSubmit('unlink['.$template['templateid'].']', _('Unlink')))->addClass(ZBX_STYLE_BTN_LINK);
		if (array_key_exists($template['templateid'], $data['original_templates'])) {
			$unlinkAndClearButton =
				(new CSubmit('unlink_and_clear['.$template['templateid'].']', _('Unlink and clear')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addStyle('margin-left: 8px');
		}
		else {
			$unlinkAndClearButton = null;
		}

		$linkedTemplateTable->addRow([$templateLink, [$unlinkButton, $unlinkAndClearButton]], null,
			'conditions_'.$template['templateid']
		);
		$ignoredTemplates[$template['templateid']] = $template['name'];
	}

	$tmplList->addRow(_('Linked templates'),
		(new CDiv($linkedTemplateTable))
			->addClass('template-link-block')
			->addClass('objectgroup')
			->addClass('inlineblock')
			->addClass('border_dotted')
	);

	// create new linked template table
	$newTemplateTable = (new CTable())
		->addClass('formElementTable')
		->setId('newTemplateTable');

	$newTemplateTable->addRow([new CMultiSelect([
		'name' => 'add_templates[]',
		'objectName' => 'templates',
		'ignored' => $ignoredTemplates,
		'popup' => [
			'parameters' => 'srctbl=templates&srcfld1=hostid&srcfld2=host&dstfrm='.$frmHost->getName().
				'&dstfld1=add_templates_&templated_hosts=1&multiselect=1'
		]
	])]);

	$newTemplateTable->addRow([(new CSubmit('add_template', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)]);

	$tmplList->addRow(_('Link new templates'),
		(new CDiv($newTemplateTable))
			->addClass('template-link-block')
			->addClass('objectgroup')
			->addClass('inlineblock')
			->addClass('border_dotted')
	);
}
// templates for discovered hosts
else {
	$linkedTemplateTable->setHeader([_('Name')]);
	foreach ($data['linked_templates'] as $template) {
		$tmplList->addVar('templates[]', $template['templateid']);
		$templateLink = (new CLink($template['name'], 'templates.php?form=update&templateid='.$template['templateid']))
			->setTarget('_blank');

		$linkedTemplateTable->addRow($templateLink, null, 'conditions_'.$template['templateid']);
	}

	$tmplList->addRow(_('Linked templates'),
		(new CDiv($linkedTemplateTable))
			->addClass('template-link-block')
			->addClass('objectgroup')
			->addClass('inlineblock')
			->addClass('border_dotted')
	);
}

$divTabs->addTab('templateTab', _('Templates'), $tmplList);

/*
 * IPMI
 */
$ipmiList = new CFormList();

if ($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED) {
	$cmbIPMIAuthtype = new CListBox('ipmi_authtype', $data['ipmi_authtype'], 7, null, ipmiAuthTypes());
	$cmbIPMIPrivilege = new CListBox('ipmi_privilege', $data['ipmi_privilege'], 5, null, ipmiPrivileges());
}
else {
	$cmbIPMIAuthtype = [
		new CTextBox('ipmi_authtype_name', ipmiAuthTypes($data['ipmi_authtype']), ZBX_TEXTBOX_SMALL_SIZE, true),
		new CVar('ipmi_authtype', $data['ipmi_authtype'])
	];
	$cmbIPMIPrivilege = [
		new CTextBox('ipmi_privilege_name', ipmiPrivileges($data['ipmi_privilege']), ZBX_TEXTBOX_SMALL_SIZE, true),
		new CVar('ipmi_privilege', $data['ipmi_privilege'])
	];
}

$ipmiList->addRow(_('Authentication algorithm'), $cmbIPMIAuthtype);
$ipmiList->addRow(_('Privilege level'), $cmbIPMIPrivilege);
$ipmiList->addRow(_('Username'), new CTextBox('ipmi_username', $data['ipmi_username'], ZBX_TEXTBOX_SMALL_SIZE, ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED)));
$ipmiList->addRow(_('Password'), new CTextBox('ipmi_password', $data['ipmi_password'], ZBX_TEXTBOX_SMALL_SIZE, ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED)));
$divTabs->addTab('ipmiTab', _('IPMI'), $ipmiList);

/*
 * Macros
 */
$macrosView = new CView('hostmacros', [
	'macros' => $data['macros'],
	'show_inherited_macros' => $data['show_inherited_macros'],
	'readonly' => ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED)
]);
$divTabs->addTab('macroTab', _('Macros'), $macrosView->render());

$inventoryFormList = new CFormList('inventorylist');

// radio buttons for inventory type choice
$inventoryDisabledBtn = (new CRadioButton('inventory_mode', HOST_INVENTORY_DISABLED, ($data['inventory_mode'] == HOST_INVENTORY_DISABLED)))
	->setId('host_inventory_radio_'.HOST_INVENTORY_DISABLED)
	->setEnabled($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED);

$inventoryManualBtn = (new CRadioButton('inventory_mode', HOST_INVENTORY_MANUAL, ($data['inventory_mode'] == HOST_INVENTORY_MANUAL)))
	->setId('host_inventory_radio_'.HOST_INVENTORY_MANUAL)
	->setEnabled($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED);

$inventoryAutomaticBtn = (new CRadioButton('inventory_mode', HOST_INVENTORY_AUTOMATIC, ($data['inventory_mode'] == HOST_INVENTORY_AUTOMATIC)))
	->setId('host_inventory_radio_'.HOST_INVENTORY_AUTOMATIC)
	->setEnabled($data['flags'] != ZBX_FLAG_DISCOVERY_CREATED);

$inventoryTypeRadioButton = [
	$inventoryDisabledBtn, new CLabel(_('Disabled'), 'host_inventory_radio_'.HOST_INVENTORY_DISABLED),
	$inventoryManualBtn, new CLabel(_('Manual'), 'host_inventory_radio_'.HOST_INVENTORY_MANUAL),
	$inventoryAutomaticBtn, new CLabel(_('Automatic'), 'host_inventory_radio_'.HOST_INVENTORY_AUTOMATIC)
];
$inventoryFormList->addRow(null,
	(new CDiv($inventoryTypeRadioButton))
		->addClass('jqueryinputset')
		->addClass('radioset')
);
if ($data['flags'] == ZBX_FLAG_DISCOVERY_CREATED) {
	$inventoryFormList->addVar('inventory_mode', $data['inventory_mode']);
}

$hostInventoryTable = DB::getSchema('host_inventory');
$hostInventoryFields = getHostInventories();

foreach ($hostInventoryFields as $inventoryNo => $inventoryInfo) {
	$field_name = $inventoryInfo['db_field'];

	if (!array_key_exists($field_name, $data['host_inventory'])) {
		$data['host_inventory'][$field_name] = '';
	}

	if ($hostInventoryTable['fields'][$field_name]['type'] == DB::FIELD_TYPE_TEXT) {
		$input = new CTextArea('host_inventory['.$field_name.']', $data['host_inventory'][$field_name]);
		$input->addStyle('width: 64em;');
	}
	else {
		$field_length = $hostInventoryTable['fields'][$field_name]['length'];
		$input = new CTextBox('host_inventory['.$field_name.']', $data['host_inventory'][$field_name]);
		$input->setAttribute('maxlength', $field_length);
		$input->addStyle('width: '.($field_length > 64 ? 64 : $field_length).'em;');
	}

	if ($data['inventory_mode'] == HOST_INVENTORY_DISABLED) {
		$input->setAttribute('disabled', 'disabled');
	}

	// link to populating item at the right side (if any)
	if (array_key_exists($inventoryNo, $data['inventory_items'])) {
		$name = $data['inventory_items'][$inventoryNo]['name_expanded'];

		$link = (new CLink($name, 'items.php?form=update&itemid='.$data['inventory_items'][$inventoryNo]['itemid']))
			->setAttribute('title', _s('This field is automatically populated by item "%s".', $name));

		$inventory_item = (new CSpan([' &larr; ', $link]))->addClass('populating_item');
		if ($data['inventory_mode'] != HOST_INVENTORY_AUTOMATIC) {
			// those links are visible only in automatic mode
			$inventory_item->addStyle('display: none');
		}

		// this will be used for disabling fields via jquery
		$input->addClass('linked_to_item');
		if ($data['inventory_mode'] == HOST_INVENTORY_AUTOMATIC) {
			$input->setAttribute('disabled', 'disabled');
		}
	}
	else {
		$inventory_item = null;
	}
	$input->addStyle('float: left;');

	$inventoryFormList->addRow($inventoryInfo['title'], [$input, $inventory_item]);
}

// clearing the float
$clearFixDiv = (new CDiv())->addStyle('clear: both;');
$inventoryFormList->addRow('', $clearFixDiv);

$divTabs->addTab('inventoryTab', _('Host inventory'), $inventoryFormList);

// Encryption
$encryptionFormList = new CFormList('encryption');

$encryptionFormList->addRow(_('Connections to host'), new CComboBox('tls_connect', $data['tls_connect'], null, [
	HOST_ENCRYPTION_NONE => _('No encryption'),
	HOST_ENCRYPTION_PSK => _('PSK'),
	HOST_ENCRYPTION_CERTIFICATE => _('Certificate')
]));
$encryptionFormList->addRow(_('Connections from host'), [
	[new CCheckBox('tls_in_none'), _('No encryption')],
	BR(),
	[new CCheckBox('tls_in_psk'), _('PSK')],
	BR(),
	[new CCheckBox('tls_in_cert'), _('Certificate')]
]);
$encryptionFormList->addRow(_('PSK identity'), new CTextBox('tls_psk_identity', $data['tls_psk_identity'], 64));
$encryptionFormList->addRow(_('PSK'), new CTextBox('tls_psk', $data['tls_psk'], 64, false, 512));
$encryptionFormList->addRow(_('Issuer'), new CTextBox('tls_issuer', $data['tls_issuer'], 64));
$encryptionFormList->addRow(_('Subject'), new CTextBox('tls_subject', $data['tls_subject'], 64));

$divTabs->addTab('encryptionTab', _('Encryption'), $encryptionFormList);

/*
 * footer
 */
// Do not display the clone and delete buttons for clone forms and new host forms.
if ($data['hostid'] != 0) {
	$divTabs->setFooter(makeFormFooter(
		new CSubmit('update', _('Update')),
		[
			new CSubmit('clone', _('Clone')),
			new CSubmit('full_clone', _('Full clone')),
			new CButtonDelete(_('Delete selected host?'), url_param('form').url_param('hostid').url_param('groupid')),
			new CButtonCancel(url_param('groupid'))
		]
	));
}
else {
	$divTabs->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_param('groupid'))]
	));
}

$frmHost->addItem($divTabs);

$widget->addItem($frmHost);

return $widget;
