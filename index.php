<?php
// SHADOWHacker-GOD :: Perfected Monolith Protocol

// NEW: PHP Proxy for fetching device profiles from Pastebin
// This avoids browser CORS (Cross-Origin) security issues.
if (isset($_GET['action']) && $_GET['action'] === 'get_devices') {
    header('Content-Type: text/plain');
    $pastebin_url = 'https://pastebin.com/raw/CKRUEDnp';

    // Set a user-agent to mimic a browser, as some services might block generic requests
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ];
    $context = stream_context_create($options);
    $content = @file_get_contents($pastebin_url, false, $context);

    if ($content === false) {
        http_response_code(500);
        echo "Error: Could not fetch device profiles from Pastebin.";
    } else {
        echo $content;
    }
    exit; // Stop script execution after sending the data
}

// Suppress deprecated notices to ensure clean output, but show other errors.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

// This block handles the server-side persistence (saving the configuration).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Invalid request.'];

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (isset($data['packageName']) && isset($data['config'])) {
        $packageName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $data['packageName']); // Sanitize
        $config = $data['config'];

        if (empty($packageName)) {
            $response['message'] = 'Package name cannot be empty.';
        } else {
            $jsonString = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (json_last_error() !== JSON_ERROR_NONE) {
                 $response['message'] = 'Error encoding configuration to JSON: ' . json_last_error_msg();
            } else {
                // Use basename() for extra security against path traversal
                $filename = __DIR__ . '/' . basename($packageName . '_cloneSettings.json');

                if (file_put_contents($filename, $jsonString) !== false) {
                    $response = ['status' => 'success', 'message' => "Configuration saved successfully to '{$filename}'."];
                } else {
                    $response['message'] = 'Error: Failed to write configuration file. Check server permissions for the directory.';
                }
            }
        }
    }

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appcloner Maker - Floating Editor</title>
    <style>
        :root {
          --primary-blue: #1a73e8;
          --hover-blue: #1557b0;
          --bg-gray: #f8f9fa;
          --border-gray: #dadce0;
          --success-green: #34a853;
          --error-red: #ea4335;
        }
        body {
          font-family: 'Roboto', sans-serif;
          margin: 0;
          padding: 16px;
          background: white;
        }
        #package-info {
            display: none !important;
        }
        .container {
          max-width: 800px;
          margin: 0 auto;
        }
        .category {
          margin-bottom: 24px;
          border: 1px solid var(--border-gray);
          border-radius: 8px;
        }
        .category-header {
          background: var(--primary-blue);
          color: white;
          padding: 12px 16px;
          border-radius: 8px 8px 0 0;
          font-weight: 500;
          cursor: pointer;
          display: flex;
          justify-content: space-between;
          align-items: center;
        }
        .category-content {
          padding: 8px;
          display: none;
        }
        .category-content.active {
          display: block;
        }
        .chevron { transition: transform 0.3s; }
        .chevron.active { transform: rotate(180deg); }
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 12px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .setting-row:last-child {
            border-bottom: none;
        }
        .setting-row:hover {
            background-color: var(--bg-gray);
        }
        .setting-row-label {
            font-weight: 500;
            color: #333;
        }
        .setting-row-value {
            font-size: 14px;
            color: #555;
            background-color: #eef2ff;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 500;
            color: var(--primary-blue);
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .save-btn {
          position: fixed;
          bottom: 24px;
          right: 24px;
          background: var(--primary-blue);
          color: white;
          padding: 12px 24px;
          border-radius: 24px;
          border: none;
          font-size: 16px;
          font-weight: 500;
          cursor: pointer;
          box-shadow: 0 2px 4px rgba(0,0,0,0.2);
          z-index: 1000;
        }
        .floating-editor-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }
        .floating-editor-overlay.visible {
            display: flex;
            opacity: 1;
        }
        .floating-editor-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 500px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            transition: transform 0.2s ease-in-out;
        }
        .floating-editor-overlay.visible .floating-editor-content {
            transform: scale(1);
        }
        .floating-editor-header {
            padding: 1rem 1.5rem;
            font-size: 1.2rem;
            font-weight: 500;
            color: #111;
            border-bottom: 1px solid var(--border-gray);
        }
        .floating-editor-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex-grow: 1;
        }
        .floating-editor-footer {
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            border-top: 1px solid var(--border-gray);
            background-color: var(--bg-gray);
            border-radius: 0 0 8px 8px;
        }
        .editor-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            background-color: transparent;
            color: var(--primary-blue);
        }
        .editor-button.primary {
            background-color: var(--primary-blue);
            color: white;
        }
        .editor-button:hover { background-color: rgba(0,0,0,0.05); }
        .editor-button.primary:hover { background-color: var(--hover-blue); }
        .editor-setting { margin-bottom: 20px; }
        .editor-setting-label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .editor-child-settings {
            margin-top: 15px;
            padding-left: 15px;
            border-left: 2px solid var(--border-gray);
            transition: opacity 0.3s;
        }
        .editor-child-settings.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        .editor-checkbox-row {
            display: flex;
            align-items: center;
            padding: 8px;
            background: var(--bg-gray);
            border-radius: 4px;
        }
        .editor-simple-checkbox-row {
            display: flex;
            align-items: center;
            padding: 12px 0;
        }
        .editor-radio-row {
            display: flex;
            align-items: center;
            padding: 8px 0;
        }
        .editor-radio-row input[type="radio"] {
             margin-right: 10px;
        }
        .editor-checkbox-row input[type="checkbox"],
        .editor-simple-checkbox-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            accent-color: var(--primary-blue);
        }
        .editor-checkbox-row label,
        .editor-simple-checkbox-row label {
            font-weight: 500;
            color: #333;
        }
        .editor-setting input[type="text"], .editor-setting input[type="number"], .editor-setting select, .editor-setting textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-gray);
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .editor-setting .custom-input-container { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
        .editor-setting .generate-btn { padding: 8px 12px; background-color: var(--primary-blue); color: white; border: none; border-radius: 4px; cursor: pointer; flex-shrink: 0; }
        .editor-setting textarea { min-height: 120px; font-family: monospace; }
        .editor-sub-group {
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
        }
        .editor-sub-group-title {
            font-weight: 500;
            margin-bottom: 1rem;
            color: #444;
            border-bottom: 1px solid var(--border-gray);
            padding-bottom: 0.5rem;
        }
        .editor-tab-nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
            margin-bottom: -1px;
        }
        .editor-tab {
            padding: 6px 12px;
            border: 1px solid var(--border-gray);
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            cursor: pointer;
            background-color: var(--bg-gray);
            font-size: 14px;
        }
        .editor-tab.active {
            background-color: white;
            border-bottom: 1px solid white;
            font-weight: 500;
            color: var(--primary-blue);
        }
        .editor-tab-content-wrapper {
            border: 1px solid var(--border-gray);
            padding: 1rem;
            border-radius: 0 8px 8px 8px;
        }
        #loading, #error-message { text-align: center; padding: 20px; }
        #error-message { color: var(--error-red); background: #ffebee; border-radius: 4px; }
        .editor-tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 8px;
            border: 1px solid var(--border-gray);
            border-radius: 4px;
            margin-bottom: 8px;
            background-color: white;
        }
        .editor-tag {
            background-color: #eef2ff;
            color: var(--primary-blue);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .editor-tag .remove-tag {
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            line-height: 1;
        }
    </style>
</head>
<body>

<div class="container">
  <div id="package-info">
    <p><strong>📦 Package:</strong> <span id="packageNameDisplay">Not detected</span></p>
    <p><strong>🔢 Split Count:</strong> <span id="splitCountDisplay">101</span></p>
  </div>
  <div id="loading">⏳ Loading configuration...</div>
  <div id="error-message" class="error-message" style="display: none;"></div>
  <div id="config-container"></div>
  <button class="save-btn" onclick="saveConfig()">💾 Save Settings</button>
</div>

<!-- Floating Editor Structure -->
<div id="floating-editor-overlay" class="floating-editor-overlay">
    <div id="floating-editor-content" class="floating-editor-content">
        <div id="floating-editor-header" class="floating-editor-header">Edit Setting</div>
        <div id="floating-editor-body" class="floating-editor-body"></div>
        <div class="floating-editor-footer">
            <button id="editor-cancel-btn" class="editor-button">Cancel</button>
            <button id="editor-save-btn" class="editor-button primary">OK</button>
        </div>
    </div>
</div>

<script>
// --- EMBEDDED CURATED DEVICE LIST ---
const DEVICES_CSV_DATA = `Google,google,Pixel 8 Pro,husky,34;35
Google,google,Pixel 7a,lynx,33;34
Samsung,samsung,Galaxy S24 Ultra,e3q,34
Samsung,samsung,Galaxy A54 5G,a54x,33;34
OnePlus,OnePlus,OnePlus 12,OP5929L1,34
OnePlus,OnePlus,OnePlus Nord 3 5G,OP556FL1,33;34
Xiaomi,Xiaomi,Xiaomi 14 Ultra,aurora,34
Redmi,Redmi,Redmi Note 13 Pro 5G,garnet,33;34
Asus,asus,ROG Phone 7 series,ASUS_AI2205,34
Asus,asus,Zenfone 10,ASUS_AI2302,34
`;

// --- Enhanced Constants and State ---
// CRITICAL SECURITY WARNING: This key is public. Any interaction should be proxied through your server.
const BIN_ID = '67fe9e1f8960c979a585d694';
const API_KEY = '$2a$10$SgT4qoOKXP6CD4u1jPEpduwi.2NbrCqV2u71AaL7mGaW.77CmNU7u';
const DEVICE_PROFILES_URL = '?action=get_devices'; // UPDATED: Fetch via our PHP proxy

let jsonConfig = {};
let originalJsonConfig = {};
let currentPackageName = "";
let configFileSplitCount = 101;
let allChildKeys = new Set();
let allParentKeys = new Set();
let parentChildMap = new Map();

let parsedDevices = [];
let deviceProfiles = []; // To store data from Pastebin

const keysWithCustomOption = [
    'changeAndroidId', 'changeImei', 'changeAndroidSerial', 'changeWifiMacAddress',
    'changeBluetoothMacAddress', 'changeImsi', 'changeGoogleAdvertisingId', 'changeGoogleServiceFrameworkId',
    'changeFacebookAttributionId', 'changeAppSetId', 'changeOpenId', 'changeAmazonAdvertisingId',
    'changeHuaweiAdvertisingId', 'changeLocale', 'changeEthernetMacAddress'
];

const NON_INTERACTIVE_DIRECTORY_KEYS = ['addPermissions', 'addProviders', 'addReceivers','addServices','addActivities','stringsProperties','serialFormat'];
const CUSTOM_EDITORS = ['webViewUrlDataFilterList', 'overrideSharedPreferences', 'customBuildProps', 'webViewCookies', 'webViewOverrideUrlLoadingList', 'skipDialogsStrings'];

const compoundSettingsMap = new Map([
    ['dnsOverHttps', ['dnsOverHttpsCustomUrl', 'dnsOverHttpsSilent']],
    ['customBuildPropsFile', ['customBuildPropsFileEnablePlaceholders']]
]);
const compoundSettingChildren = new Set(
    Array.from(compoundSettingsMap.values()).flat()
);
compoundSettingChildren.add('overrideSharedPreferencesEnablePlaceholders');

const ANDROID_SDK_VERSIONS = [
    { sdk: 23, name: 'Android 6.0 (Marshmallow)' },
    { sdk: 24, name: 'Android 7.0 (Nougat)' },
    { sdk: 25, name: 'Android 7.1 (Nougat)' },
    { sdk: 26, name: 'Android 8.0 (Oreo)' },
    { sdk: 27, name: 'Android 8.1 (Oreo)' },
    { sdk: 28, name: 'Android 9 (Pie)' },
    { sdk: 29, name: 'Android 10' },
    { sdk: 30, name: 'Android 11' },
    { sdk: 31, name: 'Android 12' },
    { sdk: 32, name: 'Android 12L' },
    { sdk: 33, name: 'Android 13' },
    { sdk: 34, name: 'Android 14' }
].reverse();

const KEYS_TO_UNICODE_ESCAPE = ['customBuildPropsFile'];

document.addEventListener('DOMContentLoaded', loadConfiguration);

function parseDeviceData() {
    parsedDevices = DEVICES_CSV_DATA
        .trim()
        .split('\n')
        .map(line => {
            const parts = line.split(',');
            if (parts.length < 5) return null;
            return {
                manufacturer: parts[0].trim(),
                brand: parts[1].trim(),
                model: parts[2].trim(),
                device: parts[3].trim(),
                sdks: parts[4].split(';').map(s => parseInt(s.trim(), 10)).filter(num => !isNaN(num))
            };
        })
        .filter(Boolean);
}

// Function to fetch and parse device profiles via our PHP proxy
async function fetchAndParseDeviceProfiles() {
    try {
        const response = await fetch(DEVICE_PROFILES_URL);
        if (!response.ok) {
            console.error('Failed to fetch device profiles via proxy.', await response.text());
            return;
        }
        const text = await response.text();

        // Maps Pastebin keys to our config keys
        const propMapping = {
            'ro.product.name': 'buildPropsDeviceName',
            'ro.product.manufacturer': 'buildPropsManufacturer',
            'ro.product.brand': 'buildPropsBrand',
            'ro.product.model': 'buildPropsModel',
            'ro.build.product': 'buildPropsProduct',
            'ro.build.device': 'buildPropsDevice',
            'ro.product.board': 'buildPropsBoard',
            'ro.boot.radio': 'buildPropsRadio',
            'ro.hardware': 'buildPropsHardware'
        };

        const profilesText = text.split(/#\s*#+/).filter(p => p.trim() !== '');

        deviceProfiles = profilesText.map(profileText => {
            const lines = profileText.trim().split('\n');
            const displayNameLine = lines.find(l => l.startsWith('# ') && !l.startsWith('##'));
            const displayName = displayNameLine ? displayNameLine.replace('# ', '').trim() : 'Unnamed Profile';

            const props = {};
            lines.forEach(line => {
                if (line.startsWith('ro.')) {
                    const parts = line.split('=', 2);
                    if (parts.length === 2) {
                        const key = parts[0].trim();
                        const value = parts[1].trim();
                        const mappedKey = propMapping[key];
                        if (mappedKey) {
                            props[mappedKey] = value;
                        }
                    }
                }
            });
            return { displayName, props };
        });

    } catch (error) {
        console.error('Error fetching or parsing device profiles:', error);
    }
}

async function loadConfiguration() {
    const loadingEl = document.getElementById('loading');
    const errorEl = document.getElementById('error-message');
    try {
        const response = await fetch(`https://api.jsonbin.io/v3/b/${BIN_ID}/latest`, { headers: { 'X-Master-Key': API_KEY } });
        if (!response.ok) throw new Error(`Failed to load configuration (Status: ${response.status})`);
        const data = await response.json();
        jsonConfig = data.record;
        originalJsonConfig = JSON.parse(JSON.stringify(jsonConfig));

        parseDeviceData();
        await fetchAndParseDeviceProfiles(); // Fetch the device profiles

        analyzeParentChildRelationships();
        renderConfiguration(jsonConfig);
    } catch (error) {
        errorEl.innerHTML = `<strong>❌ Error:</strong> ${error.message}`;
        errorEl.style.display = 'block';
    } finally {
        loadingEl.style.display = 'none';
    }
}

function analyzeParentChildRelationships() {
    allChildKeys.clear(); allParentKeys.clear(); parentChildMap.clear();
    for (const category in jsonConfig) {
        const settings = jsonConfig[category];
        for (const key in settings) {
            if (typeof settings[key] === 'object' && settings[key] !== null && !Array.isArray(settings[key])) {
                allParentKeys.add(key);
                const children = Object.keys(settings[key]).filter(childKey => childKey !== 'enabled');
                parentChildMap.set(key, children);
                children.forEach(child => allChildKeys.add(child));
            }
        }
    }

    const manualBooleanParents = {
        'addSnow': 'snow', 'pictureInPictureSupport': 'pictureInPicture', 'buildsProps': 'buildProps'
    };
    const explicitParentChildGroups = {
        'changeInstallUpdateTime': ['customInstallUpdateTime', 'randomizeUserCreationTime', 'relativeInstallUpdateTime', 'relativeInstallUpdateTimeUnit'],
        'randomizeBuildProps': ['filterDevicesDatabase', 'devicesDatabaseFilters', 'devicesDatabaseUseAndroidVersion', 'devicesDatabaseSdkVersions', 'randomizeBuildPropsDeviceNamePrefix'],
        'spoofLocation': ['latitude', 'longitude', 'spoofRandomLocation', 'spoofLocationUseIpLocation', 'spoofLocationApi', 'spoofLocationCalculateBearing', 'spoofLocationCompatibilityMode', 'spoofLocationInterval', 'spoofLocationShareLocationReceiver', 'spoofLocationShowSpoofLocationNotification', 'spoofLocationSimulatePositionalUncertainty', 'favoriteLocationsShowDistance'],
        'webViewPrivacyOptions': ['webViewDisableWebRtc', 'webViewDisableWebGl', 'webViewDisableAudioContext'],
        'webViewUrlDataMonitor': ['webViewUrlDataMonitorAutoCopy', 'webViewUrlDataMonitorAutoOpen', 'webViewUrlDataMonitorFilter', 'webViewUrlDataMonitorFilterStrings', 'webViewUrlDataMonitorRegularExpression', 'webViewUrlDataMonitorShowJavaScriptUrls', 'webViewUrlDataMonitorShowOverrideUrlLoading', 'webViewUrlDataMonitorUrlDecode'],
        'showWebViewSourceCode': ['showWebViewIFrameSourceCode', 'showWebViewSourceCodeFilter', 'showWebViewSourceCodeFilterStrings', 'showWebViewSourceCodeRegularExpression']
    };
    for (const category in jsonConfig) {
        const settings = jsonConfig[category];
        for (const key in settings) {
            if (typeof settings[key] === 'boolean') {
                const relatedKeys = Object.keys(settings).filter(otherKey => key !== otherKey && otherKey.startsWith(key) && otherKey.length > key.length && otherKey.charAt(key.length) === otherKey.charAt(key.length).toUpperCase());
                if (relatedKeys.length > 0) { allParentKeys.add(key); parentChildMap.set(key, relatedKeys); relatedKeys.forEach(child => allChildKeys.add(child)); }
            }
        }
        for (const parentKey in manualBooleanParents) {
            if (settings.hasOwnProperty(parentKey)) {
                const prefix = manualBooleanParents[parentKey];
                const children = Object.keys(settings).filter(k => k.startsWith(prefix) && k !== parentKey);
                if (children.length > 0) { allParentKeys.add(parentKey); parentChildMap.set(parentKey, children); children.forEach(child => allChildKeys.add(child)); }
            }
        }
        for (const parentKey in explicitParentChildGroups) {
            const children = explicitParentChildGroups[parentKey];
            if (children.some(child => settings.hasOwnProperty(child)) || settings.hasOwnProperty(parentKey)) {
                allParentKeys.add(parentKey);
                parentChildMap.set(parentKey, children);
                children.forEach(child => allChildKeys.add(child));
            }
        }

        const placeholderChildSuffix = 'EnablePlaceholders';
        for (const key in settings) {
            if (key.startsWith('custom') && key.endsWith(placeholderChildSuffix)) {
                const middlePart = key.substring('custom'.length, key.length - placeholderChildSuffix.length);
                const potentialParentKey = 'change' + middlePart;
                if (settings.hasOwnProperty(potentialParentKey)) {
                    allParentKeys.add(potentialParentKey);
                    allChildKeys.add(key);
                    const existingChildren = parentChildMap.get(potentialParentKey) || [];
                    parentChildMap.set(potentialParentKey, [...new Set([...existingChildren, key])]);
                }
            }
        }
    }
}

function updatePackageName(packageName) { currentPackageName = packageName; document.getElementById('packageNameDisplay').textContent = packageName; }
function updateSplitCount(count) { configFileSplitCount = count; document.getElementById('splitCountDisplay').textContent = count; }

function renderConfiguration(config) {
    const container = document.getElementById('config-container');
    container.innerHTML = '';
    Object.entries(config).forEach(([category, settings]) => {
        const categoryElement = document.createElement('div');
        categoryElement.className = 'category';
        const header = document.createElement('div');
        header.className = 'category-header';
        header.innerHTML = `<span>${formatLabel(category)}</span><span class="chevron">⌵</span>`;
        const content = document.createElement('div');
        content.className = 'category-content';
        header.onclick = () => { content.classList.toggle('active'); header.querySelector('.chevron').classList.toggle('active'); };

        for (const key in settings) {
            if (Object.hasOwnProperty.call(settings, key) && !allChildKeys.has(key) && !compoundSettingChildren.has(key) && !NON_INTERACTIVE_DIRECTORY_KEYS.includes(key)) {
                content.appendChild(createSettingRow(key, settings[key], category));
            }
        }

        if (content.hasChildNodes()) {
            categoryElement.appendChild(header);
            categoryElement.appendChild(content);
            container.appendChild(categoryElement);
        }
    });
}

function createSettingRow(key, value, category) {
    const row = document.createElement('div');
    row.className = 'setting-row';
    row.dataset.key = key; row.dataset.category = category;
    row.onclick = () => openFloatingEditor(key, category);
    const label = document.createElement('div');
    label.className = 'setting-row-label';
    label.textContent = formatLabel(key);
    const valueDisplay = document.createElement('div');
    valueDisplay.className = 'setting-row-value';
    valueDisplay.textContent = getSummaryText(key, value, category);
    row.appendChild(label); row.appendChild(valueDisplay);
    return row;
}

function getSummaryText(key, value, category) {
    if (CUSTOM_EDITORS.includes(key)) {
        const count = Array.isArray(value) ? value.length : 0;
        return `${count} item(s)`;
    }
    const isObjectParent = typeof value === 'object' && value !== null && !Array.isArray(value);
    const isBooleanParent = allParentKeys.has(key) && typeof value === 'boolean';
    if (isObjectParent) return value.enabled ? "Enabled" : "Disabled";
    if (isBooleanParent || typeof value === 'boolean') return value ? "Enabled" : "Disabled";
    if (Array.isArray(value)) {
        if (value.length === 0) return "[0 items]";
        const originalOptions = originalJsonConfig[category]?.[key];
        if (keysWithCustomOption.includes(key) && Array.isArray(originalOptions) && !originalOptions.includes(value[0])) return "Custom";
        if (typeof value[0] === 'string') return value[0];
        return `[${value.length} items]`;
    }
    if (typeof value === 'string' && value.length > 20) return value.substring(0, 17) + '...';
    return value || 'Not set';
}

const editorOverlay = document.getElementById('floating-editor-overlay');
const editorHeader = document.getElementById('floating-editor-header');
const editorBody = document.getElementById('floating-editor-body');

function openFloatingEditor(key, category) {
    editorHeader.textContent = formatLabel(key);
    editorBody.innerHTML = '';
    editorBody.appendChild(createEditorControls(key, category));
    editorOverlay.dataset.key = key; editorOverlay.dataset.category = category;
    editorOverlay.classList.add('visible');
    const firstInput = editorBody.querySelector('input, select, textarea');
    if (firstInput) setTimeout(() => firstInput.focus(), 100);
}

function closeFloatingEditor() { editorOverlay.classList.remove('visible'); }

function saveEditorChanges() {
    const key = editorOverlay.dataset.key;
    const category = editorOverlay.dataset.category;

    editorBody.querySelectorAll('[data-path]').forEach(input => {
        const path = input.dataset.path.split('.');

        if (path[path.length - 1].endsWith('_enabled')) {
             return;
        }

        let value;
        let current = jsonConfig;
        for (let i = 0; i < path.length - 1; i++) {
            if (current === null || typeof current !== 'object') return;
            current = current[path[i]];
        }
        if (current === null || typeof current !== 'object') return;

        if (input.type === 'radio') {
            if (input.checked) {
                current[path[path.length - 1]] = input.value;
            }
            return;
        }

        if (input.dataset.type === 'array-textarea') { value = input.value.split('\n').map(s => s.trim()).filter(Boolean); }
        else if (input.type === 'checkbox') { value = input.checked; }
        else if (input.type === 'number') { value = input.value === '' ? '' : (parseFloat(input.value) || 0); }
        else if (input.type === 'date') { value = new Date(input.value).getTime() }
        else { value = input.value; }

        const keyToUpdate = path[path.length - 1];
        if (input.dataset.type === 'array-textarea') { current[keyToUpdate] = value; }
        else if (input.tagName === 'SELECT' && value === 'custom') { const customInput = editorBody.querySelector(`[data-path="${input.dataset.path}-custom"]`); current[keyToUpdate] = [customInput.value]; }
        else if (Array.isArray(current[keyToUpdate]) && !key.toLowerCase().includes('strings') && !key.toLowerCase().includes('filters')) { current[keyToUpdate] = [value]; }
        else { current[keyToUpdate] = value; }
    });

    const settingRow = document.querySelector(`.setting-row[data-key="${key}"][data-category="${category}"]`);
    if (settingRow) {
        const valueDisplay = settingRow.querySelector('.setting-row-value');
        if (valueDisplay) {
            const updatedValue = jsonConfig[category][key];
            valueDisplay.textContent = getSummaryText(key, updatedValue, category);
        }
    }

    closeFloatingEditor();
}

document.getElementById('editor-save-btn').onclick = saveEditorChanges;
document.getElementById('editor-cancel-btn').onclick = closeFloatingEditor;
editorOverlay.onclick = (e) => { if (e.target === editorOverlay) closeFloatingEditor(); };
document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && editorOverlay.classList.contains('visible')) closeFloatingEditor(); });

function createEditorControls(key, category) {
    const container = document.createElement('div');

    if (compoundSettingsMap.has(key)) {
        container.appendChild(buildCompoundSettingEditor(key, category));
    }
    else if (allParentKeys.has(key)) {
        container.appendChild(buildParentEditor(key, category));
    }
    else if (CUSTOM_EDITORS.includes(key)) {
        if (key === 'webViewUrlDataFilterList') container.appendChild(buildWebViewUrlDataFilterEditor(key, category));
        else if (key === 'overrideSharedPreferences') container.appendChild(buildOverridePreferencesEditor(key, category));
        else if (key === 'customBuildProps') container.appendChild(buildCustomBuildPropsEditor(key, category));
        else if (key === 'webViewCookies') container.appendChild(buildWebViewCookieEditor(key, category));
        else if (key === 'webViewOverrideUrlLoadingList') container.appendChild(buildWebViewOverrideUrlLoadingEditor(key, category));
        else if (key === 'skipDialogsStrings') container.appendChild(buildSkipDialogsEditor(key, category));
    }
    else {
        container.appendChild(buildSimpleControl(key, jsonConfig[category][key], `${category}.${key}`, category));
    }
    return container;
}

function buildParentEditor(key, category) {
    // Special handling for buildsProps to include the device selector
    if (key === 'buildsProps') {
        const container = document.createElement('div');
        const isEnabled = jsonConfig[category][key];

        const parentToggleDiv = buildSimpleControl('Enable Builds Props', isEnabled, `${category}.${key}`, category, true);
        container.appendChild(parentToggleDiv);

        const childContainer = document.createElement('div');
        childContainer.className = `editor-child-settings ${!isEnabled ? 'disabled' : ''}`;
        parentToggleDiv.querySelector('input[type="checkbox"]').onchange = (e) => {
            childContainer.classList.toggle('disabled', !e.target.checked);
        };
        container.appendChild(childContainer);

        // Create and add the device profile selector dropdown
        if (deviceProfiles && deviceProfiles.length > 0) {
            const selectorGroup = document.createElement('div');
            selectorGroup.className = 'editor-setting';
            selectorGroup.innerHTML = `<label class="editor-setting-label">Load device profile</label>`;
            const select = document.createElement('select');
            select.add(new Option('Select a device to auto-fill...', ''));

            deviceProfiles.forEach((profile, index) => {
                select.add(new Option(profile.displayName, index));
            });

            select.onchange = (e) => {
                const selectedIndex = e.target.value;
                if (selectedIndex === '') return;

                const selectedProfile = deviceProfiles[selectedIndex];
                if (!selectedProfile) return;

                for (const propKey in selectedProfile.props) {
                    const propValue = selectedProfile.props[propKey];
                    if (jsonConfig[category].hasOwnProperty(propKey)) {
                        jsonConfig[category][propKey] = propValue;
                    }
                    const inputElement = childContainer.querySelector(`[data-path="${category}.${propKey}"]`);
                    if (inputElement) {
                        inputElement.value = propValue;
                    }
                }
            };
            selectorGroup.appendChild(select);
            childContainer.appendChild(selectorGroup);
        }

        // Create the individual input fields for each child property
        const children = parentChildMap.get(key) || [];
        children.forEach(childKey => {
            if (jsonConfig[category].hasOwnProperty(childKey)) {
                const childValue = jsonConfig[category][childKey];
                const childDataPath = `${category}.${childKey}`;
                childContainer.appendChild(buildSimpleControl(childKey, childValue, childDataPath, category));
            }
        });
        return container;
    }

    if (key === 'randomizeBuildProps') {
        return buildRandomizeBuildPropsEditor(key, category);
    }
    if (key === 'changeInstallUpdateTime') {
        return buildInstallTimeEditor(key, category);
    }

    const container = document.createElement('div');
    const value = jsonConfig[category][key];
    const children = parentChildMap.get(key) || [];
    const dataPath = `${category}.${key}`;

    if (key === 'webViewUrlDataMonitor') { return buildWebViewMonitorEditor(key, category); }
    if (key === 'showWebViewSourceCode') { return buildShowWebViewSourceCodeEditor(key, category); }

    if (key === 'webViewPrivacyOptions') {
        children.forEach(childKey => {
            if (jsonConfig[category].hasOwnProperty(childKey)) {
                container.appendChild(buildSimpleControl(childKey, jsonConfig[category][childKey], `${category}.${childKey}`, category));
            }
        });
        return container;
    }

    const isObjectType = typeof value === 'object' && value !== null && !Array.isArray(value);

    if (isObjectType || typeof value === 'boolean') {
        const isEnabled = isObjectType ? value.enabled : value;
        const parentToggleDiv = buildSimpleControl(formatLabel(key), isEnabled, isObjectType ? `${dataPath}.enabled` : dataPath, category, true);
        const childContainer = document.createElement('div');
        childContainer.className = `editor-child-settings ${!isEnabled ? 'disabled' : ''}`;
        parentToggleDiv.querySelector('input[type="checkbox"]').onchange = (e) => { childContainer.classList.toggle('disabled', !e.target.checked); };

        if (key === 'spoofLocation') {
            const coordsContainer = document.createElement('div');
            const latValue = jsonConfig[category]['latitude'] !== undefined ? jsonConfig[category]['latitude'] : '';
            const lonValue = jsonConfig[category]['longitude'] !== undefined ? jsonConfig[category]['longitude'] : '';
            const latEditor = buildSimpleControl('Latitude', latValue, `${category}.latitude`, category);
            const lonEditor = buildSimpleControl('Longitude', lonValue, `${category}.longitude`, category);

            const mapContainer = document.createElement('div');
            mapContainer.style.cssText = 'position: relative; margin: 15px 0; border: 1px solid var(--border-gray);';
            const mapImage = document.createElement('img');
            mapImage.src = 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ec/World_map_blank_without_borders.svg/1200px-World_map_blank_without_borders.svg.png';
            mapImage.style.cssText = 'width: 100%; display: block; background-color: black;';
            const mapDot = document.createElement('div');
            mapDot.style.cssText = 'position: absolute; width: 10px; height: 10px; background-color: #ff4136; border-radius: 50%; transform: translate(-50%, -50%); border: 1px solid white; display: none;';
            mapContainer.append(mapImage, mapDot);

            const updateMapDot = (lat, lon) => {
                const latitude = parseFloat(lat);
                const longitude = parseFloat(lon);
                if (isNaN(latitude) || isNaN(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
                    mapDot.style.display = 'none';
                    return;
                }
                mapDot.style.display = 'block';
                const x = ((longitude + 180) % 360) / 360 * 100;
                const y = ((-1 * latitude) + 90) / 180 * 100;
                mapDot.style.left = `${x}%`;
                mapDot.style.top = `${y}%`;
            };

            const latInput = latEditor.querySelector('input');
            const lonInput = lonEditor.querySelector('input');

            latInput.addEventListener('input', () => updateMapDot(latInput.value, lonInput.value));
            lonInput.addEventListener('input', () => updateMapDot(latInput.value, lonInput.value));

            setTimeout(() => updateMapDot(latInput.value, lonInput.value), 0);

            const buttonContainer = document.createElement('div');
            buttonContainer.style.cssText = 'display: flex; gap: 8px; margin-top: 10px;';

            const pickPlaceBtn = document.createElement('button');
            pickPlaceBtn.textContent = 'Pick place';
            pickPlaceBtn.type = 'button';
            pickPlaceBtn.className = 'editor-button primary';
            pickPlaceBtn.style.flexGrow = '1';

            const randomizeBtn = document.createElement('button');
            randomizeBtn.textContent = 'Randomize';
            randomizeBtn.type = 'button';
            randomizeBtn.className = 'editor-button primary';
            randomizeBtn.style.flexGrow = '1';

            randomizeBtn.onclick = () => {
                const coords = generateRealisticCoordinates();
                latInput.value = coords.latitude;
                lonInput.value = coords.longitude;
                updateMapDot(coords.latitude, coords.longitude);
                latInput.dispatchEvent(new Event('input', { bubbles: true }));
                lonInput.dispatchEvent(new Event('input', { bubbles: true }));
            };

            buttonContainer.append(pickPlaceBtn, randomizeBtn);
            coordsContainer.append(latEditor, lonEditor, mapContainer, buttonContainer);
            const otherOptionsContainer = document.createElement('div');
            children.forEach(childKey => {
                if(jsonConfig[category].hasOwnProperty(childKey) && !['latitude', 'longitude'].includes(childKey)){
                     otherOptionsContainer.appendChild(buildSimpleControl(childKey, jsonConfig[category][childKey], `${category}.${childKey}`, category));
                }
            });
            childContainer.append(coordsContainer, otherOptionsContainer);

        } else {
            children.forEach(childKey => {
                const isInternalChild = isObjectType && value.hasOwnProperty(childKey);
                const childValue = isInternalChild ? value[childKey] : jsonConfig[category][childKey];
                if (childValue !== undefined) {
                    const childDataPath = isInternalChild ? `${dataPath}.${childKey}` : `${category}.${childKey}`;
                    childContainer.appendChild(buildSimpleControl(childKey, childValue, childDataPath, category));
                }
            });
        }
        container.appendChild(parentToggleDiv);
        container.appendChild(childContainer);
    }
    else {
        const parentControl = buildSimpleControl(key, value, dataPath, category);
        container.appendChild(parentControl);
        if (children.length > 0) {
            const childContainer = document.createElement('div');
            childContainer.className = 'editor-child-settings';
            children.forEach(childKey => {
                const childValue = jsonConfig[category][childKey];
                childContainer.appendChild(buildSimpleControl(childKey, childValue, `${category}.${childKey}`, category));
            });
            container.appendChild(childContainer);
        }
    }
    return container;
}

function buildCompoundSettingEditor(key, category) {
    const container = document.createElement('div');
    const children = compoundSettingsMap.get(key) || [];
    const parentControl = buildSimpleControl(key, jsonConfig[category][key], `${category}.${key}`, category);
    container.appendChild(parentControl);
    const select = parentControl.querySelector('select');
    const childContainer = document.createElement('div');
    childContainer.className = 'editor-child-settings';
    children.forEach(childKey => {
        if (jsonConfig[category].hasOwnProperty(childKey)) {
             childContainer.appendChild(buildSimpleControl(childKey, jsonConfig[category][childKey], `${category}.${childKey}`, category));
        }
    });
    if (select) {
        const updateVisibility = () => {
            const isCustom = (select.value || '').toUpperCase() === 'CUSTOM';
            childContainer.style.display = isCustom ? 'block' : 'none';
        };
        select.addEventListener('change', updateVisibility);
        updateVisibility();
    }
    container.appendChild(childContainer);
    return container;
}

function buildSimpleControl(key, value, dataPath, category, isParentToggle = false) {
    const settingDiv = document.createElement('div');
    const originalValue = getValueByPath(originalJsonConfig, dataPath ? dataPath.split('.') : []);
    const labelText = formatLabel(key);
    const uniqueId = `control-id-${dataPath ? dataPath.replace(/\./g, '-') : key.replace(/ /g, '-')}`;

    const isBooleanControl = typeof value === 'boolean' || key.endsWith('EnablePlaceholders') || key === 'removeAllCookies';

    if (isBooleanControl) {
        const useSimpleCheckbox = key.startsWith('webViewDisable') || key === 'nameRegExp' || key.startsWith('webViewUrlDataMonitor') || key.startsWith('showWebView') || key === 'removeAllCookies' || ANDROID_SDK_VERSIONS.some(v => v.name === key) || key.startsWith('spoofLocation');
        const className = useSimpleCheckbox ? 'editor-simple-checkbox-row' : 'editor-checkbox-row';
        let label = isParentToggle ? `${labelText}` : labelText;
        if(useSimpleCheckbox) label = labelText.replace(/Web View Url Data Monitor /i, '').replace(/Show Web View /i, '').replace(/Web View Cookies /i, '');
        settingDiv.className = useSimpleCheckbox ? '' : 'editor-setting';
        const dataPathAttr = dataPath ? `data-path="${dataPath}"` : '';
        settingDiv.innerHTML = `<div class="${className}"><input type="checkbox" id="${uniqueId}" ${dataPathAttr} ${value ? 'checked' : ''}><label for="${uniqueId}">${label}</label></div>`;
    } else if (Array.isArray(originalValue) && !key.toLowerCase().includes('strings') && !key.toLowerCase().includes('filters')) {
        settingDiv.className = 'editor-setting';
        settingDiv.innerHTML = `<label class="editor-setting-label" for="${uniqueId}">${labelText}</label>`;
        const isCustom = Array.isArray(value) && value.length > 0 && !originalValue.includes(value[0]);
        const select = document.createElement('select');
        select.id = uniqueId; select.dataset.path = dataPath;
        originalValue.forEach(opt => select.add(new Option(opt, opt)));
        if (keysWithCustomOption.includes(key)) { select.add(new Option('CUSTOM', 'custom')); }
        select.value = isCustom ? 'custom' : (value?.[0] || originalValue[0]);
        settingDiv.appendChild(select);
        if (keysWithCustomOption.includes(key)) {
            const customContainer = document.createElement('div');
            customContainer.className = 'custom-input-container';
            customContainer.style.display = isCustom ? 'flex' : 'none';
            const customInput = document.createElement('input');
            customInput.type = 'text'; customInput.dataset.path = `${dataPath}-custom`;
            customInput.value = isCustom ? value[0] : '';
            customContainer.appendChild(customInput);
            if (keysWithCustomOption.includes(key)) {
                const generateBtn = document.createElement('button');
                generateBtn.type = 'button'; generateBtn.className = 'generate-btn'; generateBtn.textContent = 'Generate';
                generateBtn.onclick = () => { customInput.value = generateRandomValue(key); };
                customContainer.appendChild(generateBtn);
            }
            select.onchange = () => customContainer.style.display = select.value === 'custom' ? 'flex' : 'none';
            settingDiv.appendChild(customContainer);
        }
    } else if (Array.isArray(value) && (key.toLowerCase().includes('strings') || key.toLowerCase().includes('filters'))) {
        settingDiv.className = 'editor-setting';
        settingDiv.innerHTML = `<label class="editor-setting-label" for="${uniqueId}">${labelText}</label>`;
        const textarea = document.createElement('textarea');
        textarea.id = uniqueId; textarea.value = value.join('\n'); textarea.dataset.path = dataPath;
        textarea.dataset.type = 'array-textarea'; textarea.placeholder = 'Enter one item per line...';
        settingDiv.appendChild(textarea);
    } else if (key === 'customBuildPropsFile' || key === 'webViewUrlDataMonitorRegularExpression' || key === 'showWebViewSourceCodeRegularExpression') {
        settingDiv.className = 'editor-setting';
        settingDiv.innerHTML = `<label class="editor-setting-label" for="${uniqueId}">${labelText}</label>`;
        const textarea = document.createElement('textarea');
        textarea.id = uniqueId; textarea.value = value; textarea.dataset.path = dataPath;
        settingDiv.appendChild(textarea);
    }
     else {
        settingDiv.className = 'editor-setting';
        settingDiv.innerHTML = `<label class="editor-setting-label" for="${uniqueId}">${labelText}</label>`;
        const input = document.createElement('input');
        input.id = uniqueId; input.type = typeof value === 'number' ? 'number' : 'text';
        input.value = value || ''; input.dataset.path = dataPath;
        if(key === 'webViewUrlDataMonitorFilterStrings' || key === 'showWebViewSourceCodeFilterStrings') input.placeholder = 'Enter a string';
        if (input.type === 'number') input.step = 'any';
        settingDiv.appendChild(input);
    }
    return settingDiv;
}

// --- ALL OTHER build... functions like buildWebViewCookieEditor, buildSkipDialogsEditor etc. are included below without changes ---

function buildSkipDialogsEditor(key, category) {
    const container = document.createElement('div');
    const settings = jsonConfig[category];

    if (!Array.isArray(settings.skipDialogsStrings)) settings.skipDialogsStrings = [];
    if (!Array.isArray(settings.skipDialogsStacktraceStrings)) settings.skipDialogsStacktraceStrings = [];
    if (typeof settings.skipDialogsMonitorStacktraces !== 'boolean') settings.skipDialogsMonitorStacktraces = false;

    const group1 = document.createElement('div');
    group1.className = 'editor-setting';
    group1.innerHTML = `<label class="editor-setting-label" for="skip-dialogs-strings-editor">Don't show dialogs if they contain one of these strings</label>`;
    const textarea1 = document.createElement('textarea');
    textarea1.id = 'skip-dialogs-strings-editor';
    textarea1.value = settings.skipDialogsStrings.join('\n');
    textarea1.dataset.path = `${category}.skipDialogsStrings`;
    textarea1.dataset.type = 'array-textarea';
    textarea1.placeholder = 'Enter a string';
    group1.appendChild(textarea1);
    container.appendChild(group1);

    const group2 = document.createElement('div');
    group2.className = 'editor-setting';
    group2.innerHTML = `<label class="editor-setting-label" for="skip-stacktrace-strings-editor">Don't show dialogs if their call stack contains one of these strings</label>`;
    const textarea2 = document.createElement('textarea');
    textarea2.id = 'skip-stacktrace-strings-editor';
    textarea2.value = settings.skipDialogsStacktraceStrings.join('\n');
    textarea2.dataset.path = `${category}.skipDialogsStacktraceStrings`;
    textarea2.dataset.type = 'array-textarea';
    textarea2.placeholder = 'Enter a string';
    group2.appendChild(textarea2);
    container.appendChild(group2);

    const monitorControl = buildSimpleControl(
        "Monitor dialog call stacks",
        settings.skipDialogsMonitorStacktraces,
        `${category}.skipDialogsMonitorStacktraces`,
        category
    );
    container.appendChild(monitorControl);

    return container;
}


function buildShowWebViewSourceCodeEditor(key, category) {
    const container = document.createElement('div');
    const mainToggle = buildSimpleControl('Enabled', jsonConfig[category][key], `${category}.${key}`, category, false);
    container.appendChild(mainToggle);

    const childContainer = document.createElement('div');
    childContainer.className = 'editor-child-settings';
    mainToggle.querySelector('input').addEventListener('change', e => {
        childContainer.style.display = e.target.checked ? 'block' : 'none';
    });
    childContainer.style.display = jsonConfig[category][key] ? 'block' : 'none';

    childContainer.appendChild(buildSimpleControl('showWebViewIFrameSourceCode', jsonConfig[category]['showWebViewIFrameSourceCode'], `${category}.showWebViewIFrameSourceCode`, category));

    const filterGroup = document.createElement('div');
    filterGroup.className = 'editor-setting';
    filterGroup.innerHTML = '<label class="editor-setting-label">Filter</label>';
    const filterOptions = ['DISABLED', 'INCLUDE', 'EXCLUDE'];
    const currentFilter = jsonConfig[category]['showWebViewSourceCodeFilter'] || 'DISABLED';
    filterOptions.forEach(opt => {
        const radioRow = document.createElement('div');
        radioRow.className = 'editor-radio-row';
        const id = `${category}-source-filter-${opt}`;
        radioRow.innerHTML = `<input type="radio" id="${id}" name="${category}-source-filter" value="${opt}" data-path="${category}.showWebViewSourceCodeFilter" ${currentFilter === opt ? 'checked' : ''}><label for="${id}">${formatLabel(opt)}</label>`;
        filterGroup.appendChild(radioRow);
    });
    childContainer.appendChild(filterGroup);

    childContainer.appendChild(buildSimpleControl('Filter Strings', jsonConfig[category]['showWebViewSourceCodeFilterStrings'], `${category}.showWebViewSourceCodeFilterStrings`, category));
    childContainer.appendChild(buildSimpleControl('Regular expression', jsonConfig[category]['showWebViewSourceCodeRegularExpression'], `${category}.showWebViewSourceCodeRegularExpression`, category));

    container.appendChild(childContainer);
    return container;
}


function buildWebViewMonitorEditor(key, category) {
    const container = document.createElement('div');
    const mainToggle = buildSimpleControl('Enabled', jsonConfig[category][key], `${category}.${key}`, category, false);
    container.appendChild(mainToggle);

    const childContainer = document.createElement('div');
    childContainer.className = 'editor-child-settings';
    mainToggle.querySelector('input').addEventListener('change', e => {
        childContainer.style.display = e.target.checked ? 'block' : 'none';
    });
    childContainer.style.display = jsonConfig[category][key] ? 'block' : 'none';

    childContainer.appendChild(buildSimpleControl('webViewUrlDataMonitorShowJavaScriptUrls', jsonConfig[category]['webViewUrlDataMonitorShowJavaScriptUrls'], `${category}.webViewUrlDataMonitorShowJavaScriptUrls`, category));
    childContainer.appendChild(buildSimpleControl('webViewUrlDataMonitorShowOverrideUrlLoading', jsonConfig[category]['webViewUrlDataMonitorShowOverrideUrlLoading'], `${category}.webViewUrlDataMonitorShowOverrideUrlLoading`, category));

    const filterGroup = document.createElement('div');
    filterGroup.className = 'editor-setting';
    filterGroup.innerHTML = '<label class="editor-setting-label">Filter</label>';
    const filterOptions = ['DISABLED', 'INCLUDE', 'EXCLUDE'];
    const currentFilter = jsonConfig[category]['webViewUrlDataMonitorFilter'] || 'DISABLED';
    filterOptions.forEach(opt => {
        const radioRow = document.createElement('div');
        radioRow.className = 'editor-radio-row';
        const id = `${category}-monitor-filter-${opt}`;
        radioRow.innerHTML = `<input type="radio" id="${id}" name="${category}-monitor-filter" value="${opt}" data-path="${category}.webViewUrlDataMonitorFilter" ${currentFilter === opt ? 'checked' : ''}><label for="${id}">${formatLabel(opt)}</label>`;
        filterGroup.appendChild(radioRow);
    });
    childContainer.appendChild(filterGroup);

    childContainer.appendChild(buildSimpleControl('Filter Strings', jsonConfig[category]['webViewUrlDataMonitorFilterStrings'], `${category}.webViewUrlDataMonitorFilterStrings`, category));
    childContainer.appendChild(buildSimpleControl('Regular expression', jsonConfig[category]['webViewUrlDataMonitorRegularExpression'], `${category}.webViewUrlDataMonitorRegularExpression`, category));

    childContainer.appendChild(buildSimpleControl('webViewUrlDataMonitorUrlDecode', jsonConfig[category]['webViewUrlDataMonitorUrlDecode'], `${category}.webViewUrlDataMonitorUrlDecode`, category));
    childContainer.appendChild(buildSimpleControl('webViewUrlDataMonitorAutoCopy', jsonConfig[category]['webViewUrlDataMonitorAutoCopy'], `${category}.webViewUrlDataMonitorAutoCopy`, category));
    childContainer.appendChild(buildSimpleControl('webViewUrlDataMonitorAutoOpen', jsonConfig[category]['webViewUrlDataMonitorAutoOpen'], `${category}.webViewUrlDataMonitorAutoOpen`, category));

    container.appendChild(childContainer);
    return container;
}

function buildRandomizeBuildPropsEditor(key, category) {
    const container = document.createElement('div');
    const settings = jsonConfig[category];

    if (!settings.devicesDatabaseSdkVersions || typeof settings.devicesDatabaseSdkVersions !== 'object') {
        settings.devicesDatabaseSdkVersions = {};
    }
    if (!settings.devicesDatabaseFilters || !Array.isArray(settings.devicesDatabaseFilters)) {
        settings.devicesDatabaseFilters = [];
    }
    const filters = settings.devicesDatabaseFilters;

    const mainToggle = buildSimpleControl('Enabled', settings[key], `${category}.${key}`, category, true);
    container.appendChild(mainToggle);

    const childContainer = document.createElement('div');
    childContainer.className = 'editor-child-settings';
    container.appendChild(childContainer);

    const updateChildVisibility = (isEnabled) => {
        childContainer.style.display = isEnabled ? 'block' : 'none';
    };
    mainToggle.querySelector('input').addEventListener('change', e => {
        settings[key] = e.target.checked;
        updateChildVisibility(e.target.checked);
    });
    updateChildVisibility(settings[key]);

    const filterDevicesGroup = document.createElement('div');
    const filterDevicesToggle = buildSimpleControl('Filter devices', settings.filterDevicesDatabase, `${category}.filterDevicesDatabase`, category);
    filterDevicesGroup.appendChild(filterDevicesToggle);

    const filterEditorContainer = document.createElement('div');
    filterEditorContainer.className = 'editor-child-settings';
    filterDevicesGroup.appendChild(filterEditorContainer);

    const updateFilterEditorVisibility = (isEnabled) => {
        filterEditorContainer.style.display = isEnabled ? 'block' : 'none';
    };
    filterDevicesToggle.querySelector('input').addEventListener('change', e => {
        settings.filterDevicesDatabase = e.target.checked;
        updateFilterEditorVisibility(e.target.checked);
    });
    updateFilterEditorVisibility(settings.filterDevicesDatabase);

    const tagsContainer = document.createElement('div');
    tagsContainer.className = 'editor-tags-container';

    const renderTags = () => {
        tagsContainer.innerHTML = '';
        filters.forEach((tag, index) => {
            const tagEl = document.createElement('span');
            tagEl.className = 'editor-tag';
            tagEl.textContent = tag;
            const removeEl = document.createElement('span');
            removeEl.className = 'remove-tag';
            removeEl.textContent = '×';
            removeEl.onclick = () => {
                filters.splice(index, 1);
                renderTags();
            };
            tagEl.appendChild(removeEl);
            tagsContainer.appendChild(tagEl);
        });
    };

    const filterInput = document.createElement('input');
    filterInput.type = 'text';
    filterInput.placeholder = 'Add filter (e.g., Samsung, -Pixel)';
    filterInput.style.width = '100%';
    filterInput.onkeydown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const newTag = filterInput.value.trim();
            if (newTag && !filters.includes(newTag)) {
                filters.push(newTag);
                renderTags();
            }
            filterInput.value = '';
        }
    };
    const filterHint = document.createElement('p');
    filterHint.textContent = 'Exclude results using a - (minus) prefix.';
    filterHint.style.cssText = 'font-size: 12px; color: #555; margin: 4px 0 0;';

    filterEditorContainer.append(tagsContainer, filterInput, filterHint);
    renderTags();
    childContainer.appendChild(filterDevicesGroup);

    const versionGroup = document.createElement('div');
    versionGroup.style.marginTop = '20px';
    const versionToggle = buildSimpleControl('Filter Android versions', settings.devicesDatabaseUseAndroidVersion, `${category}.devicesDatabaseUseAndroidVersion`, category);
    versionGroup.appendChild(versionToggle);

    const sdkListContainer = document.createElement('div');
    sdkListContainer.className = 'editor-child-settings';
    sdkListContainer.style.maxHeight = '150px';
    sdkListContainer.style.overflowY = 'auto';
    sdkListContainer.style.border = '1px solid var(--border-gray)';
    sdkListContainer.style.padding = '8px';
    sdkListContainer.style.borderRadius = '4px';

    ANDROID_SDK_VERSIONS.forEach(version => {
        const sdkString = version.sdk.toString();
        const checkboxRow = buildSimpleControl(version.name, settings.devicesDatabaseSdkVersions[sdkString] === true, null, category);
        const checkbox = checkboxRow.querySelector('input');
        checkbox.onchange = (e) => {
            const sdkVersions = jsonConfig[category].devicesDatabaseSdkVersions;
            if (e.target.checked) {
                sdkVersions[sdkString] = true;
            } else {
                delete sdkVersions[sdkString];
            }
        };
        sdkListContainer.appendChild(checkboxRow);
    });

    versionGroup.appendChild(sdkListContainer);
    const updateSdkListVisibility = (isEnabled) => {
        sdkListContainer.style.display = isEnabled ? 'block' : 'none';
    };
    versionToggle.querySelector('input').addEventListener('change', e => {
        settings.devicesDatabaseUseAndroidVersion = e.target.checked;
        updateSdkListVisibility(e.target.checked);
    });
    updateSdkListVisibility(settings.devicesDatabaseUseAndroidVersion);

    childContainer.appendChild(versionGroup);

    const previewButton = document.createElement('button');
    previewButton.textContent = 'Preview';
    previewButton.type = 'button';
    previewButton.className = 'editor-button primary';
    previewButton.style.width = '100%';
    previewButton.style.marginTop = '20px';
    previewButton.onclick = () => {
        let filtered = [...parsedDevices];

        if (settings.filterDevicesDatabase && filters.length > 0) {
            const includeFilters = filters.filter(f => !f.startsWith('-')).map(f => f.toLowerCase());
            const excludeFilters = filters.filter(f => f.startsWith('-')).map(f => f.substring(1).toLowerCase());

            if (includeFilters.length > 0) {
                filtered = filtered.filter(d =>
                    includeFilters.some(f => d.manufacturer.toLowerCase().includes(f) || d.brand.toLowerCase().includes(f))
                );
            }
            if (excludeFilters.length > 0) {
                filtered = filtered.filter(d =>
                    !excludeFilters.some(f => d.manufacturer.toLowerCase().includes(f) || d.brand.toLowerCase().includes(f))
                );
            }
        }

        if (settings.devicesDatabaseUseAndroidVersion) {
            const selectedSdks = Object.keys(settings.devicesDatabaseSdkVersions).map(Number);
            if (selectedSdks.length > 0) {
                filtered = filtered.filter(d =>
                    d.sdks.some(sdk => selectedSdks.includes(sdk))
                );
            }
        }

        if (filtered.length === 0) {
            alert('No devices match the current filters.');
            return;
        }

        const randomDevice = filtered[Math.floor(Math.random() * filtered.length)];

        alert(
            `Random Preview:\n\n` +
            `Manufacturer: ${randomDevice.manufacturer}\n` +
            `Model: ${randomDevice.model}\n` +
            `Device: ${randomDevice.device}\n` +
            `SDK Version: ${randomDevice.sdks[Math.floor(Math.random() * randomDevice.sdks.length)]}`
        );
    };
    childContainer.appendChild(previewButton);


    const prefixInput = buildSimpleControl('Device name prefix', settings.randomizeBuildPropsDeviceNamePrefix, `${category}.randomizeBuildPropsDeviceNamePrefix`, category);
    prefixInput.style.marginTop = '20px';
    childContainer.appendChild(prefixInput);

    return container;
}

function buildWebViewUrlDataFilterEditor(key, category) {
    const container = document.createElement('div');
    if (!Array.isArray(jsonConfig[category][key])) {
        jsonConfig[category][key] = [];
    }
    const list = jsonConfig[category][key];
    const defaultFilter = {
        urlExpression: "",
        urlExpressionBlockOnMatch: false,
        urlReplacement: "",
        urlReplacementUrlEncode: false,
        dataExpression: "",
        dataExpressionIgnoreCase: false,
        dataExpressionBlockOnMatch: false,
        dataReplacement: "",
        dataReplacementReplaceAll: false
    };

    if (list.length === 0) list.push({ ...defaultFilter });
    let currentIndex = 0;

    const header = document.createElement('div');
    header.style.cssText = 'display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;';
    const tabNav = document.createElement('div');
    tabNav.className = 'editor-tab-nav';
    const actions = document.createElement('div');
    actions.style.cssText = 'display: flex; gap: 0.5rem;';
    const addBtn = document.createElement('button');
    addBtn.textContent = '➕';
    addBtn.className = 'editor-button';
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = '🗑️';
    deleteBtn.className = 'editor-button';
    actions.append(addBtn, deleteBtn);
    header.append(tabNav, actions);
    const formWrapper = document.createElement('div');
    formWrapper.className = 'editor-tab-content-wrapper';

    const renderCurrentFilter = () => {
        tabNav.innerHTML = '';
        formWrapper.innerHTML = '';
        list.forEach((_, index) => {
            const tab = document.createElement('span');
            tab.className = 'editor-tab';
            tab.textContent = index + 1;
            if (index === currentIndex) tab.classList.add('active');
            tab.onclick = () => { currentIndex = index; renderCurrentFilter(); };
            tabNav.appendChild(tab);
        });
        deleteBtn.disabled = list.length === 0;
        if (list.length === 0) {
            list.push({ ...defaultFilter });
            currentIndex = 0;
            renderCurrentFilter();
            return;
        }

        const currentFilter = list[currentIndex];

        const createField = (label, prop, type = 'text') => {
            const control = buildSimpleControl(label, currentFilter[prop] || (type === 'checkbox' ? false : ''), null, category);
            const input = control.querySelector('input');
            if (type === 'checkbox') {
                input.onchange = (e) => { currentFilter[prop] = e.target.checked; };
            } else {
                input.oninput = (e) => { currentFilter[prop] = e.target.value; };
            }
            return control;
        };

        const urlGroup = document.createElement('div');
        urlGroup.className = 'editor-sub-group';
        urlGroup.innerHTML = '<div class="editor-sub-group-title">URL</div>';
        urlGroup.appendChild(createField('Regular expression', 'urlExpression'));
        urlGroup.appendChild(createField('Block if matching', 'urlExpressionBlockOnMatch', 'checkbox'));
        urlGroup.appendChild(createField('Replacement', 'urlReplacement'));
        urlGroup.appendChild(createField('URL-encode replacement', 'urlReplacementUrlEncode', 'checkbox'));

        const dataGroup = document.createElement('div');
        dataGroup.className = 'editor-sub-group';
        dataGroup.innerHTML = '<div class="editor-sub-group-title">Data</div>';
        dataGroup.appendChild(createField('Regular expression', 'dataExpression'));
        dataGroup.appendChild(createField('Ignore case', 'dataExpressionIgnoreCase', 'checkbox'));
        dataGroup.appendChild(createField('Block if matching', 'dataExpressionBlockOnMatch', 'checkbox'));
        dataGroup.appendChild(createField('Replacement', 'dataReplacement'));
        dataGroup.appendChild(createField('Replace all', 'dataReplacementReplaceAll', 'checkbox'));

        formWrapper.append(urlGroup, dataGroup);
    };

    addBtn.onclick = () => {
        list.push({ ...defaultFilter });
        currentIndex = list.length - 1;
        renderCurrentFilter();
    };
    deleteBtn.onclick = () => {
        if (list.length > 0) {
            list.splice(currentIndex, 1);
            currentIndex = Math.max(0, currentIndex - 1);
            renderCurrentFilter();
        }
    };

    container.append(header, formWrapper);
    renderCurrentFilter();
    return container;
}

function buildWebViewOverrideUrlLoadingEditor(key, category) {
    const container = document.createElement('div');
    if (!Array.isArray(jsonConfig[category][key])) {
        jsonConfig[category][key] = [];
    }
    const list = jsonConfig[category][key];
    const defaultItem = { urlExpression: "", overrideUrlLoading: true };

    if (list.length === 0) list.push({ ...defaultItem });
    let currentIndex = 0;

    const header = document.createElement('div');
    header.style.cssText = 'display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;';
    const tabNav = document.createElement('div');
    tabNav.className = 'editor-tab-nav';
    const actions = document.createElement('div');
    actions.style.cssText = 'display: flex; gap: 0.5rem;';
    const addBtn = document.createElement('button');
    addBtn.textContent = '➕';
    addBtn.className = 'editor-button';
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = '🗑️';
    deleteBtn.className = 'editor-button';
    actions.append(addBtn, deleteBtn);
    header.append(tabNav, actions);
    const formWrapper = document.createElement('div');
    formWrapper.className = 'editor-tab-content-wrapper';

    const renderCurrentItem = () => {
        tabNav.innerHTML = '';
        formWrapper.innerHTML = '';
        list.forEach((_, index) => {
            const tab = document.createElement('span');
            tab.className = 'editor-tab';
            tab.textContent = index + 1;
            if (index === currentIndex) tab.classList.add('active');
            tab.onclick = () => { currentIndex = index; renderCurrentItem(); };
            tabNav.appendChild(tab);
        });
        deleteBtn.disabled = list.length === 0;
        if (list.length === 0) {
            list.push({ ...defaultItem });
            currentIndex = 0;
            renderCurrentItem();
            return;
        }

        const currentItem = list[currentIndex];

        const urlExpressionControl = buildSimpleControl('Regular expression', currentItem.urlExpression || "", null);
        urlExpressionControl.querySelector('input').oninput = (e) => { currentItem.urlExpression = e.target.value; };

        const overrideGroup = document.createElement('div');
        overrideGroup.className = 'editor-setting';
        overrideGroup.innerHTML = '<label class="editor-setting-label">Override URL loading</label>';
        const options = [{label: 'Disabled', value: false}, {label: 'Enabled', value: true}];

        options.forEach(opt => {
            const radioRow = document.createElement('div');
            radioRow.className = 'editor-radio-row';
            const id = `${category}-override-${currentIndex}-${opt.label}`;
            const isChecked = currentItem.overrideUrlLoading === opt.value;
            radioRow.innerHTML = `<input type="radio" id="${id}" name="${category}-override-${currentIndex}" value="${opt.value}" ${isChecked ? 'checked' : ''}><label for="${id}">${opt.label}</label>`;
            radioRow.querySelector('input').onchange = (e) => {
                currentItem.overrideUrlLoading = (e.target.value === 'true');
            };
            overrideGroup.appendChild(radioRow);
        });

        formWrapper.append(urlExpressionControl, overrideGroup);
    };

    addBtn.onclick = () => {
        list.push({ ...defaultItem });
        currentIndex = list.length - 1;
        renderCurrentItem();
    };
    deleteBtn.onclick = () => {
        if (list.length > 0) {
            list.splice(currentIndex, 1);
            currentIndex = Math.max(0, currentIndex - 1);
            renderCurrentItem();
        }
    };

    container.append(header, formWrapper);
    renderCurrentItem();
    return container;
}

function buildCustomBuildPropsEditor(key, category) {
    const container = document.createElement('div');
    if (!jsonConfig[category][key] || !Array.isArray(jsonConfig[category][key])) { jsonConfig[category][key] = []; }
    const list = jsonConfig[category][key];
    const defaultProp = { name: "", value: "" };
    if (list.length === 0) list.push({ ...defaultProp });
    let currentIndex = 0;

    const header = document.createElement('div'); header.style.cssText = 'display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;';
    const tabNav = document.createElement('div'); tabNav.className = 'editor-tab-nav';
    const actions = document.createElement('div'); actions.style.cssText = 'display: flex; gap: 0.5rem;';
    const addBtn = document.createElement('button'); addBtn.textContent = '➕'; addBtn.className = 'editor-button';
    const deleteBtn = document.createElement('button'); deleteBtn.textContent = '🗑️'; deleteBtn.className = 'editor-button';
    actions.append(addBtn, deleteBtn); header.append(tabNav, actions);
    const formWrapper = document.createElement('div'); formWrapper.className = 'editor-tab-content-wrapper';

    const renderCurrentProp = () => {
        tabNav.innerHTML = ''; formWrapper.innerHTML = '';
        list.forEach((_, index) => {
            const tab = document.createElement('span'); tab.className = 'editor-tab'; tab.textContent = index + 1;
            if (index === currentIndex) tab.classList.add('active');
            tab.onclick = () => { currentIndex = index; renderCurrentProp(); };
            tabNav.appendChild(tab);
        });
        deleteBtn.disabled = list.length <= 1; if (list.length === 0) return;

        const currentProp = list[currentIndex];
        const createField = (label, prop) => {
            const control = buildSimpleControl(label, currentProp[prop], `${category}.${key}.${currentIndex}.${prop}`, category);
            control.querySelector('input').oninput = (e) => { currentProp[prop] = e.target.value; };
            return control;
        };
        formWrapper.append(
            createField('Name', 'name'),
            createField('Value', 'value')
        );
    };

    addBtn.onclick = () => { list.push({ ...defaultProp }); currentIndex = list.length - 1; renderCurrentProp(); };
    deleteBtn.onclick = () => {
        if (list.length > 0) {
            list.splice(currentIndex, 1); currentIndex = Math.max(0, currentIndex - 1);
            if (list.length === 0) { list.push({ ...defaultProp }); currentIndex = 0; }
            renderCurrentProp();
        }
    };

    container.append(header, formWrapper);
    renderCurrentProp();
    return container;
}

function buildOverridePreferencesEditor(key, category) {
    const container = document.createElement('div');
    if (!jsonConfig[category][key] || !Array.isArray(jsonConfig[category][key])) {
        jsonConfig[category][key] = [];
    }
    const list = jsonConfig[category][key];
    const defaultPref = { name: "", nameRegExp: false, value: "" };
    if (list.length === 0) list.push({ ...defaultPref });
    let currentIndex = 0;

    const header = document.createElement('div'); header.style.cssText = 'display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;';
    const tabNav = document.createElement('div'); tabNav.className = 'editor-tab-nav';
    const actions = document.createElement('div'); actions.style.cssText = 'display: flex; gap: 0.5rem;';
    const addBtn = document.createElement('button'); addBtn.textContent = '➕'; addBtn.className = 'editor-button';
    const deleteBtn = document.createElement('button'); deleteBtn.textContent = '🗑️'; deleteBtn.className = 'editor-button';
    actions.append(addBtn, deleteBtn); header.append(tabNav, actions);
    const formWrapper = document.createElement('div'); formWrapper.className = 'editor-tab-content-wrapper';

    const renderCurrentPref = () => {
        tabNav.innerHTML = ''; formWrapper.innerHTML = '';
        list.forEach((_, index) => {
            const tab = document.createElement('span'); tab.className = 'editor-tab'; tab.textContent = index + 1;
            if (index === currentIndex) tab.classList.add('active');
            tab.onclick = () => { currentIndex = index; renderCurrentPref(); };
            tabNav.appendChild(tab);
        });
        deleteBtn.disabled = list.length <= 1; if (list.length === 0) return;

        const currentPref = list[currentIndex];
        const createField = (label, prop, isCheckbox = false) => {
            const control = buildSimpleControl(label, currentPref[prop], `${category}.${key}.${currentIndex}.${prop}`, category);
            const input = control.querySelector('input');
            if (isCheckbox) {
                input.onchange = (e) => { currentPref[prop] = e.target.checked; };
            } else {
                input.oninput = (e) => { currentPref[prop] = e.target.value; };
            }
            return control;
        };
        formWrapper.append(
            createField('Name', 'name'),
            createField('Regular expression', 'nameRegExp', true),
            createField('Value', 'value')
        );
    };

    addBtn.onclick = () => { list.push({ ...defaultPref }); currentIndex = list.length - 1; renderCurrentPref(); };
    deleteBtn.onclick = () => {
        if (list.length > 0) {
            list.splice(currentIndex, 1); currentIndex = Math.max(0, currentIndex - 1);
            if (list.length === 0) { list.push({ ...defaultPref }); currentIndex = 0; }
            renderCurrentPref();
        }
    };

    container.append(header, formWrapper);

    const placeholdersCheckbox = buildSimpleControl(
        "Enable placeholders in values",
        jsonConfig[category]['overrideSharedPreferencesEnablePlaceholders'],
        `${category}.overrideSharedPreferencesEnablePlaceholders`,
        category
    );
    placeholdersCheckbox.style.marginTop = '1rem';
    container.appendChild(placeholdersCheckbox);

    renderCurrentPref();
    return container;
}

function buildWebViewCookieEditor(key, category) {
    const container = document.createElement('div');
    const isEnabled = Array.isArray(jsonConfig[category][key]);

    const mainToggle = buildSimpleControl("Enabled", isEnabled, `${category}.${key}_enabled`, category, false);
    container.appendChild(mainToggle);

    const childContainer = document.createElement('div');
    childContainer.className = 'editor-child-settings';

    mainToggle.querySelector('input').addEventListener('change', e => {
        if (e.target.checked) {
            if (!Array.isArray(jsonConfig[category][key])) {
                jsonConfig[category][key] = [];
            }
        } else {
            jsonConfig[category][key] = false;
        }
        const newEditorContent = buildWebViewCookieEditor(key, category);
        container.innerHTML = '';
        container.appendChild(newEditorContent);
    });

    if (isEnabled) {
        const list = jsonConfig[category][key];
        const defaultCookie = { name: "", path: "/", url: "", value: "" };
        if (list.length === 0) list.push({ ...defaultCookie });
        let currentIndex = 0;

        const header = document.createElement('div'); header.style.cssText = 'display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;';
        const tabNav = document.createElement('div'); tabNav.className = 'editor-tab-nav';
        const actions = document.createElement('div'); actions.style.cssText = 'display: flex; gap: 0.5rem;';
        const addBtn = document.createElement('button'); addBtn.textContent = '➕'; addBtn.className = 'editor-button';
        const deleteBtn = document.createElement('button'); deleteBtn.textContent = '🗑️'; deleteBtn.className = 'editor-button';
        actions.append(addBtn, deleteBtn); header.append(tabNav, actions);
        const formWrapper = document.createElement('div'); formWrapper.className = 'editor-tab-content-wrapper';

        const renderCurrentCookie = () => {
            tabNav.innerHTML = ''; formWrapper.innerHTML = '';
            list.forEach((_, index) => {
                const tab = document.createElement('span'); tab.className = 'editor-tab'; tab.textContent = index + 1;
                if (index === currentIndex) tab.classList.add('active');
                tab.onclick = () => { currentIndex = index; renderCurrentCookie(); };
                tabNav.appendChild(tab);
            });
            deleteBtn.disabled = list.length === 0;
            if (list.length === 0) return;
            const currentCookie = list[currentIndex];
            const createField = (label, prop) => {
                const control = buildSimpleControl(label, currentCookie[prop], `${category}.${key}.${currentIndex}.${prop}`, category);
                control.querySelector('input').oninput = (e) => { currentCookie[prop] = e.target.value; };
                return control;
            };
            formWrapper.append(createField('URL', 'url'), createField('Name', 'name'), createField('Value', 'value'), createField('Path', 'path'));
        };

        addBtn.onclick = () => { list.push({ ...defaultCookie }); currentIndex = list.length - 1; renderCurrentCookie(); };
        deleteBtn.onclick = () => {
            if (list.length > 0) {
                list.splice(currentIndex, 1); currentIndex = Math.max(0, currentIndex - 1);
                if(list.length === 0) list.push({ ...defaultCookie });
                renderCurrentCookie();
            }
        };

        childContainer.append(header, formWrapper);

        const removeAllContainer = buildSimpleControl('removeAllCookies', jsonConfig[category]['removeAllCookies'], `${category}.removeAllCookies`, category);
        childContainer.appendChild(removeAllContainer);
        renderCurrentCookie();
    }

    container.appendChild(childContainer);
    childContainer.style.display = isEnabled ? 'block' : 'none';

    return container;
}

function getValueByPath(obj, path) { return path.reduce((c, k) => (c && typeof c === 'object' && k in c) ? c[k] : undefined, obj); }
function formatLabel(key) { return key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase()).replace('Web View', 'WebView'); }
function generateRealisticCoordinates() { const r = (min, max) => Math.random() * (max - min) + min; return { latitude: r(-90, 90).toFixed(6), longitude: r(-180, 180).toFixed(6) }; }

function customUnicodeEscape(str) {
    if (typeof str !== 'string' || !str) return str;
    return str.replace(/[^a-zA-Z0-9\s_.,/:?*+$()[\]{}-]/g, (c) => {
        return '\\u' + ('0000' + c.charCodeAt(0).toString(16)).slice(-4);
    });
}

function generateRandomValue(key) {
    const _generateDigits = (len) => Array.from({ length: len }, () => Math.floor(Math.random() * 10)).join('');
    const _generateHex = (len) => Array.from({ length: len }, () => '0123456789abcdef'[Math.floor(Math.random() * 16)]).join('');
    const _generateAlphanum = (len) => Array.from({ length: len }, () => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'[Math.floor(Math.random() * 36)]).join('');
    const _generateUuidV4 = () => 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => { const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8); return v.toString(16); });
    const _generateRealisticMacAddress = () => {
        const ouiList = ['00:05:69', '00:1A:11', '00:E0:4C', '3C:5A:B4', '40:B8:9A', 'BC:F5:AC', 'D8:80:39']; // Cisco, Intel, Realtek, Google, Apple, Samsung, Foxconn
        const oui = ouiList[Math.floor(Math.random() * ouiList.length)];
        const host = Array.from({ length: 3 }, () => Math.floor(Math.random() * 256).toString(16).padStart(2, '0')).join(':');
        return `${oui}:${host}`.toUpperCase();
    };
    const _generateLuhnCheckedImei = () => {
        let imeiBase = _generateDigits(14); let sum = 0;
        for (let i = 0; i < imeiBase.length; i++) { let digit = parseInt(imeiBase[i]); if ((i % 2) !== 0) { digit *= 2; if (digit > 9) { digit = (digit % 10) + 1; } } sum += digit; }
        const checkDigit = (10 - (sum % 10)) % 10; return imeiBase + checkDigit;
    };
    const _generateRealisticImsi = () => {
        const mccMncPairs = [ {mcc: '310', mnc: '260'}, {mcc: '310', mnc: '410'}, {mcc: '234', mnc: '15'}, {mcc: '262', mnc: '01'}, {mcc: '404', mnc: '45'} ]; // T-Mobile US, AT&T US, Vodafone UK, T-Mobile DE, Airtel IN
        const pair = mccMncPairs[Math.floor(Math.random() * mccMncPairs.length)]; const msinLength = 15 - pair.mcc.length - pair.mnc.length;
        const msin = _generateDigits(msinLength); return pair.mcc + pair.mnc + msin;
    };
    const _generateAndroidSerial = () => { return Math.random() > 0.5 ? _generateAlphanum(12) : _generateHex(16); };
    const generators = {
        'changeAndroidId': () => _generateHex(16), 'changeImei': _generateLuhnCheckedImei, 'changeImsi': _generateRealisticImsi, 'changeAndroidSerial': _generateAndroidSerial,
        'changeWifiMacAddress': _generateRealisticMacAddress, 'changeBluetoothMacAddress': _generateRealisticMacAddress, 'changeEthernetMacAddress': _generateRealisticMacAddress,
        'changeGoogleAdvertisingId': _generateUuidV4, 'changeFacebookAttributionId': _generateUuidV4, 'changeAppSetId': _generateUuidV4, 'changeOpenId': _generateUuidV4,
        'changeAmazonAdvertisingId': _generateUuidV4, 'changeHuaweiAdvertisingId': _generateUuidV4, 'changeGoogleServiceFrameworkId': () => _generateHex(16),
    };
    return generators[key] ? generators[key]() : `CUSTOM_${Date.now()}`;
}

function getUpdatedConfig() {
    const flatConfig = {};
    const tempConfig = JSON.parse(JSON.stringify(jsonConfig));

    for (const category in tempConfig) {
        const settings = tempConfig[category];
        for (let key in settings) {
            if (!settings.hasOwnProperty(key)) continue;

            if (key === 'webViewUrlDataFilterList' && Array.isArray(settings[key])) {
                settings[key].forEach(item => {
                    if (item.urlExpression) item.urlExpression = customUnicodeEscape(item.urlExpression);
                    if (item.urlReplacement) item.urlReplacement = customUnicodeEscape(item.urlReplacement);
                    if (item.dataExpression) item.dataExpression = customUnicodeEscape(item.dataExpression);
                    if (item.dataReplacement) item.dataReplacement = customUnicodeEscape(item.dataReplacement);
                });
            } else if (key === 'webViewOverrideUrlLoadingList' && Array.isArray(settings[key])) {
                settings[key].forEach(item => {
                    if (item.urlExpression) item.urlExpression = customUnicodeEscape(item.urlExpression);
                });
            } else if (KEYS_TO_UNICODE_ESCAPE.includes(key) && typeof settings[key] === 'string') {
                settings[key] = customUnicodeEscape(settings[key]);
            }

            const value = settings[key];

            if (NON_INTERACTIVE_DIRECTORY_KEYS.includes(key)) {
                flatConfig[key] = originalJsonConfig[category][key];
                continue;
            }

            if (allChildKeys.has(key) && Array.from(parentChildMap.values()).flat().includes(key)) {
                let hasRealParent = false;
                for (const [p, children] of parentChildMap.entries()) {
                    if (children.includes(key) && settings.hasOwnProperty(p)) {
                        hasRealParent = true;
                        break;
                    }
                }
                if (hasRealParent) continue;
            }

            if (compoundSettingChildren.has(key)) continue;

            const isObjectParent = typeof value === 'object' && value !== null && !Array.isArray(value);
            const isBooleanParent = allParentKeys.has(key) && typeof value === 'boolean';

            if (isObjectParent) {
                if (value.enabled) {
                    flatConfig[key] = value;
                    const externalChildren = (parentChildMap.get(key) || []).filter(c => !value.hasOwnProperty(c));
                    if (externalChildren.length > 0) {
                        externalChildren.forEach(childKey => {
                            if (settings[childKey] !== undefined) flatConfig[childKey] = settings[childKey];
                        });
                    }
                }
                continue;
            }
            if (isBooleanParent) {
                if (value) {
                    flatConfig[key] = true;
                    (parentChildMap.get(key) || []).forEach(childKey => {
                        if (settings[childKey] !== undefined) flatConfig[childKey] = settings[childKey];
                    });
                }
                continue;
            }
            if (compoundSettingsMap.has(key)) {
                flatConfig[key] = Array.isArray(value) && !key.toLowerCase().includes('strings') ? value[0] : value;
                (compoundSettingsMap.get(key) || []).forEach(childKey => {
                    if (settings[childKey] !== undefined) flatConfig[childKey] = settings[childKey];
                });
                continue;
            }

            const originalValue = originalJsonConfig[category]?.[key];
            if (keysWithCustomOption.includes(key) && Array.isArray(value) && Array.isArray(originalValue) && value.length === 1 && !originalValue.includes(value[0])) {
                flatConfig[key] = "CUSTOM";
                flatConfig['custom' + key.charAt(6).toUpperCase() + key.slice(7)] = value[0];
            } else if (Array.isArray(value) && !CUSTOM_EDITORS.includes(key) && !key.toLowerCase().includes('strings') && !key.toLowerCase().includes('filters')) {
                flatConfig[key] = value[0];
            } else {
                flatConfig[key] = value;
            }
        }
    }
    return flatConfig;
}

function saveConfig() {
    let packageName = currentPackageName;
    if (!packageName) { packageName = prompt("📦 Package name not detected. Please enter it:"); if (!packageName) { alert("❌ Save cancelled: Package name required."); return; } updatePackageName(packageName); }
    try {
        const finalFlatConfig = getUpdatedConfig();

        let jsonString = JSON.stringify(finalFlatConfig, null, 2);
        jsonString = jsonString.replace(/\\\\u/g, '\\u');

        const saveBtn = document.querySelector('.save-btn'); const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '⏳ Saving...'; saveBtn.disabled = true;
        setTimeout(() => {
            if (window.Android && typeof window.Android.saveEncryptedConfig === 'function') {
                window.Android.saveEncryptedConfig(jsonString, packageName, configFileSplitCount);
                if (window.Android.showToast) window.Android.showToast("✅ Configuration saved successfully!");
            } else {
                console.warn("🌐 Browser mode: Downloading config as JSON file.");
                const blob = new Blob([jsonString], { type: 'application/json;charset=utf-8' });
                const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `${packageName}_cloneSettings.json`; a.click(); URL.revokeObjectURL(a.href);
                alert("📥 Configuration downloaded as JSON file!");
            }
            saveBtn.innerHTML = originalText; saveBtn.disabled = false;
        }, 500);
    } catch (error) { alert(`❌ Save Error: ${error.message}`); console.error("Save Error:", error); }
}
</script>
</body>
</html>