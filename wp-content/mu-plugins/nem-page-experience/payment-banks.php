<?php
/** Vietnamese bank directory (NAPAS BIN) and VietQR (EMVCo) payload builder. */
defined('ABSPATH') || exit;

function npe_vn_banks() {
    return [
        '970436' => 'Vietcombank', '970415' => 'VietinBank', '970418' => 'BIDV', '970405' => 'Agribank',
        '970407' => 'Techcombank', '970422' => 'MB Bank', '970416' => 'ACB', '970432' => 'VPBank',
        '970423' => 'TPBank', '970403' => 'Sacombank', '970437' => 'HDBank', '970441' => 'VIB',
        '970443' => 'SHB', '970431' => 'Eximbank', '970426' => 'MSB', '970448' => 'OCB',
        '970440' => 'SeABank', '970449' => 'LPBank', '970428' => 'Nam A Bank', '970409' => 'Bac A Bank',
        '970454' => 'BVBank', '970452' => 'KienlongBank', '970412' => 'PVcomBank', '970425' => 'ABBANK',
        '970427' => 'VietABank', '970419' => 'NCB', '970433' => 'VietBank', '970429' => 'SCB',
        '970400' => 'Saigonbank', '970430' => 'PGBank', '970408' => 'GPBank', '970438' => 'BaoViet Bank',
    ];
}

/** Guess the BIN from a free-text bank name entered in WooCommerce. */
function npe_bank_bin_from_name($name) {
    $needle = strtolower(preg_replace('/[^a-z0-9]/i', '', remove_accents((string) $name)));
    if ($needle === '') return '';
    $aliases = ['ngoaithuong' => '970436', 'vcb' => '970436', 'congthuong' => '970415', 'ctg' => '970415', 'dautu' => '970418',
        'nongnghiep' => '970405', 'tcb' => '970407', 'quandoi' => '970422', 'mbbank' => '970422', 'achau' => '970416',
        'vpb' => '970432', 'tienphong' => '970423', 'saigonthuongtin' => '970403', 'stb' => '970403', 'quocte' => '970441',
        'hanghai' => '970426', 'phuongdong' => '970448', 'dongnama' => '970440', 'lienviet' => '970449', 'buudien' => '970449'];
    foreach ($aliases as $alias => $bin) if (str_contains($needle, $alias)) return $bin;
    foreach (npe_vn_banks() as $bin => $label) {
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $label));
        if ($key !== '' && (str_contains($needle, $key) || str_contains($key, $needle))) return $bin;
    }
    return '';
}

/** First complete WooCommerce BACS account, with resolved BIN. */
function npe_bank_primary_account() {
    $settings = (array) get_option('woocommerce_bacs_settings', []);
    foreach ((array) get_option('woocommerce_bacs_accounts', []) as $account) {
        if (!is_array($account) || empty($account['account_number']) || empty($account['account_name']) || empty($account['bank_name'])) continue;
        $bin = preg_match('/^[0-9]{6}$/', (string) ($settings['npe_bank_bin'] ?? '')) ? $settings['npe_bank_bin'] : npe_bank_bin_from_name($account['bank_name']);
        return ['bank_name' => $account['bank_name'], 'account_name' => $account['account_name'],
            'account_number' => preg_replace('/[^0-9A-Za-z]/', '', $account['account_number']), 'bin' => $bin];
    }
    return null;
}

function npe_crc16_ccitt($data) {
    $crc = 0xFFFF;
    $length = strlen($data);
    for ($i = 0; $i < $length; $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($bit = 0; $bit < 8; $bit++) $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

/** Transfer content limited to the ASCII set every banking app accepts. */
function npe_bank_transfer_content($text) {
    return substr(trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9 ]/', '', remove_accents((string) $text)))), 0, 25);
}

/** NAPAS 247 VietQR payload, scannable by every Vietnamese banking app. */
function npe_vietqr_payload($bin, $account, $amount, $content) {
    $field = function ($id, $value) { return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value; };
    if (!preg_match('/^[0-9]{6}$/', (string) $bin) || !preg_match('/^[0-9A-Za-z]{1,19}$/', (string) $account)) return '';
    $merchant = $field('00', 'A000000727') . $field('01', $field('00', $bin) . $field('01', $account)) . $field('02', 'QRIBFTTA');
    $payload = $field('00', '01') . $field('01', $amount ? '12' : '11') . $field('38', $merchant) . $field('53', '704');
    if ($amount) $payload .= $field('54', (string) $amount);
    $payload .= $field('58', 'VN');
    $content = npe_bank_transfer_content($content);
    if ($content !== '') $payload .= $field('62', $field('08', $content));
    $payload .= '6304';
    return $payload . npe_crc16_ccitt($payload);
}

