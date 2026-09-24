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
    // 强制安全项补齐：?act= / &act= 漏拦会导致悬停敏感链接即触发删除等操作（数据丢失），
    // 用户误删时在保存阶段补回，保证「界面显示 = 实际生效」（输出层还会再兜底一次）
    $have = array();
    foreach ($clean as $l) {
        $have[strtolower($l)] = true;
    }
    foreach (at8_pagespeed_blacklist_required() as $need) {
        if (!isset($have[strtolower($need)])) {
            $clean[] = $need;
        }
    }
    $c->blacklist = implode("\n", $clean);
    $c->lazy_enabled = (GetVars('lazy_enabled', 'POST') == '1') ? 1 : 0;
    $c->lazy_skip = max(0, min(20, (int) GetVars('lazy_skip', 'POST')));
    // DNS 域名：保存时就归一化并丢弃非法项（不落脏数据；输出时还会再校验一次）
    $dnsIn = preg_split('/[\r\n]+/', trim((string) GetVars('dns_domains', 'POST')));
    $dns = array();
    foreach ($dnsIn as $d) {
        $n = at8_pagespeed_normalize_domain($d);
        if ($n !== '' && !in_array($n, $dns, true)) {
            $dns[] = $n;
        }
    }
    $c->dns_domains = implode("\n", $dns);
    $zbp->SaveConfig('at8_pagespeed');

    $zbp->SetHint('good', '配置已保存');
    // 固定回本页：不读取 HTTP_REFERER（可被伪造触发站外跳转，且直接访问时该键不存在会告警）
    Redirect('./main.php');
}

// 进入设置页时顺带把配置升到当前版本：
// Z-Blog 核心没有 UpdatePlugin()，官方升级钩子靠后台的 misc&type=updatedapp 路由触发，
// 手动覆盖文件升级的站点不会触发。在这里补一次（幂等、仅脏时写库），
// 使「界面显示 = 库里配置 = 实际生效」三者一致；输出层另有强制兜底，安全不依赖这一步。
at8_pagespeed_migrate_config();

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

$blogtitle = '页面加速';

// 使用后台官方框架输出页头与左侧菜单（不自行输出 doctype / html / body，
// 否则会脱离后台整体框架，出现「没有左侧菜单」的问题）
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<link rel="stylesheet" href="<?php echo $zbp->host; ?>zb_users/plugin/at8_pagespeed/style.css?v=<?php echo AT8_PAGESPEED_VERSION; ?>">
<div id="divMain">
	<div class="ps-wrap">

		<div class="ps-head">
			<h1>页面加速</h1>
			<p>链接悬停预加载、图片懒加载、DNS 预取。纯前端输出层优化，不注册系统业务流程相关的 Hook。</p>
		</div>

		<form method="post" action="main.php">
			<input type="hidden" name="act" value="save">
			<input type="hidden" name="csrfToken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

			<div class="ps-card">
				<div class="ps-card-title">链接预加载 <span class="ps-sub">instant.page：鼠标悬停 / 触摸按下时提前加载目标页，减少点击后的等待</span></div>
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
					<span class="ps-tip">默认已排除登录 / 退出 / 后台 / 购物车 / 支付 / 订单 / 删除 / 编辑 / Feed；其中 <code>?act=</code> 与 <code>&amp;act=</code> 用于拦 Z-Blog 的 <code>cmd.php?act=*</code> 系统操作（如 <code>ArticleDel</code>），<strong>请勿删除</strong>——预取会发出真实 GET 请求，漏拦会导致悬停敏感链接即触发该操作。匹配不区分大小写，并对 URL 编码写法（如 log%6Fut）一并拦截</span>
				</div>
			</div>

			<div class="ps-card">
				<div class="ps-card-title">图片懒加载 <span class="ps-sub">为视口外的图片与 iframe 补 loading="lazy"，由浏览器原生懒加载调度</span></div>
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
</div>
<script>if (typeof ActiveLeftMenu == "function") { ActiveLeftMenu("nav_at8_pagespeed"); }</script>
<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
