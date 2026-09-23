<?php
/**
 * 页面加速 · 后台设置页
 */
require_once dirname(__FILE__) . '/../../../zb_system/function/c_system_base.php';
require_once dirname(__FILE__) . '/../../../zb_system/function/c_system_admin.php';

$zbp->Load();

$action = 'admin'; // 官方 actions 表为小写 'admin'（大写 'Admin' 不存在，校验必失败）
if (!$zbp->CheckRights($action)) {
    $zbp->ShowError(6);
    die();
}
if (!$zbp->CheckPlugin('at8_pagespeed')) {
    $zbp->ShowError(48);
    die();
}

// 保存配置（写操作：CSRF 校验，官方姿势——失败自动 ShowError 终止）
if (GetVars('act', 'POST') === 'save') {
    CheckIsRefererValid();

    $c = $zbp->Config('at8_pagespeed');
    $c->preload_enabled = (GetVars('preload_enabled', 'POST') == '1') ? 1 : 0;
    $c->preload_delay = max(0, min(2000, (int) GetVars('preload_delay', 'POST')));
    $blacklist = trim((string) GetVars('blacklist', 'POST'));
    // 每行一个关键字，只存可见文本，防注入
    $lines = preg_split('/[\r\n]+/', $blacklist);
    $clean = array();
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l !== '' && strlen($l) <= 100) {
            $clean[] = $l;
        }
    }
    $c->blacklist = implode("\n", $clean);
    $c->lazy_enabled = (GetVars('lazy_enabled', 'POST') == '1') ? 1 : 0;
    $c->lazy_skip = max(0, min(20, (int) GetVars('lazy_skip', 'POST')));
    $c->dns_domains = trim((string) GetVars('dns_domains', 'POST'));
    $zbp->SaveConfig('at8_pagespeed');

    $zbp->SetHint('good', '配置已保存');
    Redirect($_SERVER['HTTP_REFERER']);
}

$cfg = array(
    'preload_enabled' => (int) at8_pagespeed_cfg('preload_enabled'),
    'preload_delay'   => (int) at8_pagespeed_cfg('preload_delay'),
    'blacklist'       => (string) at8_pagespeed_cfg('blacklist'),
    'lazy_enabled'    => (int) at8_pagespeed_cfg('lazy_enabled'),
    'lazy_skip'       => (int) at8_pagespeed_cfg('lazy_skip'),
    'dns_domains'     => (string) at8_pagespeed_cfg('dns_domains'),
);
// 默认命名空间 token（必须与 CheckIsRefererValid 内的 CheckCSRFTokenValid() 默认校验配套）
$csrf = $zbp->GetCSRFToken();

?><!DOCTYPE html>
<html lang="<?php echo $zbp->lang['lang']; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>页面加速 - <?php echo htmlspecialchars($zbp->name, ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="stylesheet" href="style.css?v=<?php echo AT8_PAGESPEED_VERSION; ?>">
</head>
<body>
<div class="ps-wrap">
	<div class="ps-head">
		<h1>页面加速</h1>
		<p>链接悬停预加载、图片懒加载、DNS 预取。纯前端优化，不改动系统任何业务流程。</p>
	</div>

	<?php $zbp->GetHint(); ?>

	<form method="post" action="main.php">
		<input type="hidden" name="act" value="save">
		<input type="hidden" name="csrfToken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

		<div class="ps-card">
			<div class="ps-card-title">链接预加载 <span class="ps-sub">instant.page：鼠标悬停 / 触摸按下时提前加载目标页，点击几乎零等待</span></div>
			<div class="ps-row">
				<label class="ps-switch">
					<input type="checkbox" name="preload_enabled" value="1"<?php echo $cfg['preload_enabled'] ? ' checked' : ''; ?>>
					<span>启用链接预加载</span>
				</label>
			</div>
			<div class="ps-row">
				<label>触发延迟（毫秒）</label>
				<input type="number" name="preload_delay" min="0" max="2000" step="5" value="<?php echo $cfg['preload_delay']; ?>">
				<span class="ps-tip">越小越激进（65 = 官方默认），越大越省流量</span>
			</div>
			<div class="ps-row">
				<label>预加载黑名单</label>
				<textarea name="blacklist" rows="6" placeholder="每行一个关键字，链接地址含该关键字时不预加载"><?php echo htmlspecialchars($cfg['blacklist'], ENT_QUOTES, 'UTF-8'); ?></textarea>
				<span class="ps-tip">默认已排除退出登录、后台、购物车、删除等敏感操作链接</span>
			</div>
		</div>

		<div class="ps-card">
			<div class="ps-card-title">图片懒加载 <span class="ps-sub">进入视口才加载图片与 iframe，首屏更快、流量更省</span></div>
			<div class="ps-row">
				<label class="ps-switch">
					<input type="checkbox" name="lazy_enabled" value="1"<?php echo $cfg['lazy_enabled'] ? ' checked' : ''; ?>>
					<span>启用图片懒加载</span>
				</label>
			</div>
			<div class="ps-row">
				<label>跳过首屏图片数量</label>
				<input type="number" name="lazy_skip" min="0" max="20" value="<?php echo $cfg['lazy_skip']; ?>">
				<span class="ps-tip">前 N 张图不懒加载，避免拖慢首屏大图（LCP）</span>
			</div>
		</div>

		<div class="ps-card">
			<div class="ps-card-title">DNS 预取 <span class="ps-sub">提前解析第三方资源域名（图床、统计、字体等）</span></div>
			<div class="ps-row">
				<label>附加域名</label>
				<textarea name="dns_domains" rows="4" placeholder="每行一个域名，如：&#10;cdn.example.com&#10;img.example.com"><?php echo htmlspecialchars($cfg['dns_domains'], ENT_QUOTES, 'UTF-8'); ?></textarea>
				<span class="ps-tip">站点自身域名已自动包含，无需填写</span>
			</div>
		</div>

		<div class="ps-foot">
			<button type="submit" class="ps-btn">保存配置</button>
		</div>
	</form>
</div>
<script>if (typeof ActiveLeftMenu == "function") { ActiveLeftMenu("nav_at8_pagespeed"); }</script>
</body>
</html>
