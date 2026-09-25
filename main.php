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

$act = (string) GetVars('act', 'POST');

// 清除插件配置（写操作：CSRF 校验；二次确认由前端 confirm 提供）
//
// 【为什么需要这个入口】Z-BlogPHP 1.7.5 的「停用」与「卸载」共用 UninstallPlugin_<id>()
// 钩子，在该钩子里删配置会导致用户每次停用都丢失全部设置。因此本插件在生命周期钩子中
// 一律保留配置；需要彻底清理时，由管理员在这里主动清除。
//
// 【只删自己的】官方 DelConfig($name) 只作用于传入的 name（内部 $this->configs[$name]->Delete()），
// 不会触碰系统配置、其它插件配置，也不涉及任何文章 / 用户 / 附件数据。
if ($act === 'reset') {
    CheckIsRefererValid();
    $zbp->DelConfig('at8_pagespeed');
    $zbp->SetHint('good', '已清除本插件的全部配置，并恢复为默认值');
    Redirect('./main.php');
}

// 保存配置（写操作：CSRF 校验，官方姿势——失败自动 ShowError 终止）
if ($act === 'save') {
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
    // 原样保存用户填写的关键字：不追加、不删除、不改写任何条目
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
// 手动覆盖文件升级的站点不会触发。在这里补一次（幂等、仅脏时写库）。
// 迁移只补齐缺失的配置键，不改动用户已保存的黑名单。
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
<link rel="stylesheet" href="<?php echo htmlspecialchars($zbp->host, ENT_QUOTES, 'UTF-8'); ?>zb_users/plugin/at8_pagespeed/style.css?v=<?php echo AT8_PAGESPEED_VERSION; ?>">
<div id="divMain">
	<div class="at8ps-wrap">

		<div class="at8ps-head">
			<h1>页面加速</h1>
			<p>链接悬停预加载、图片懒加载、DNS 预取。纯前端输出层优化，不注册系统业务流程相关的 Hook。</p>
		</div>

		<form method="post" action="main.php">
			<input type="hidden" name="act" value="save">
			<input type="hidden" name="csrfToken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

			<div class="at8ps-card">
				<div class="at8ps-card-title">链接预加载 <span class="at8ps-sub">instant.page：鼠标悬停 / 触摸按下时提前加载目标页，减少点击后的等待</span></div>
				<div class="at8ps-row">
					<label class="at8ps-switch">
						<input type="checkbox" name="preload_enabled" value="1"<?php echo ((int) $cfg['preload_enabled'] === 1) ? ' checked' : ''; ?>>
						<span>启用链接预加载</span>
					</label>
				</div>
				<div class="at8ps-row">
					<label>触发延迟（毫秒）</label>
					<input type="number" name="preload_delay" min="0" max="2000" step="5" value="<?php echo (int) $cfg['preload_delay']; ?>">
					<span class="at8ps-tip">越小越激进（65 = 官方默认），越大越省流量</span>
				</div>
				<div class="at8ps-row">
					<label>预加载黑名单</label>
					<textarea name="blacklist" rows="6" placeholder="每行一个关键字，链接地址含该关键字时不预加载"><?php echo htmlspecialchars($cfg['blacklist'], ENT_QUOTES, 'UTF-8'); ?></textarea>
					<span class="at8ps-tip">每行一个关键词。链接地址包含该关键词时，不进行 instant.page 预加载。本功能只控制预加载行为，不会阻止链接正常点击。匹配不区分大小写，并对 URL 编码写法（如 <code>log%6Fut</code>）一并匹配。默认值只是一组通用关键词，可自行增删</span>
				</div>
			</div>

			<div class="at8ps-card">
				<div class="at8ps-card-title">图片懒加载 <span class="at8ps-sub">为视口外的图片与 iframe 补 loading="lazy"，由浏览器原生懒加载调度</span></div>
				<div class="at8ps-row">
					<label class="at8ps-switch">
						<input type="checkbox" name="lazy_enabled" value="1"<?php echo ((int) $cfg['lazy_enabled'] === 1) ? ' checked' : ''; ?>>
						<span>启用图片懒加载</span>
					</label>
				</div>
				<div class="at8ps-row">
					<label>跳过首屏图片数量</label>
					<input type="number" name="lazy_skip" min="0" max="20" value="<?php echo (int) $cfg['lazy_skip']; ?>">
					<span class="at8ps-tip">前 N 张图不懒加载，避免拖慢首屏大图（LCP）</span>
				</div>
			</div>

			<div class="at8ps-card">
				<div class="at8ps-card-title">DNS 预取 <span class="at8ps-sub">提前解析第三方资源域名（图床、统计、字体等）</span></div>
				<div class="at8ps-row">
					<label>附加域名</label>
					<textarea name="dns_domains" rows="4" placeholder="每行一个域名，如：&#10;cdn.example.com&#10;img.example.com"><?php echo htmlspecialchars($cfg['dns_domains'], ENT_QUOTES, 'UTF-8'); ?></textarea>
					<span class="at8ps-tip">请填写网站实际使用的第三方资源域名，例如 CDN、图床、字体或统计服务域名。站点自身域名已自动包含，无需填写。</span>
					<span class="at8ps-tip">DNS 预取仅对指定域名执行 DNS 预解析（只做域名解析，不建立连接、不预取资源）；<strong>本插件不会向这些域名发送任何 API 请求</strong>，也不会代替您访问这些域名。不符合域名格式的输入会被自动丢弃。</span>
				</div>
			</div>

			<div class="at8ps-foot">
				<button type="submit" class="at8ps-btn">保存配置</button>
			</div>
		</form>

		<div class="at8ps-card at8ps-card-danger">
			<div class="at8ps-card-title">高级 <span class="at8ps-sub">本插件不建表、不写文件，全部设置只存在系统配置表中本插件自己的一行记录</span></div>
			<div class="at8ps-row">
				<span class="at8ps-tip">清除后，链接预加载、懒加载与 DNS 预取的设置都会恢复为默认值。此操作只删除 <code>at8_pagespeed</code> 自己的配置，不会影响系统配置、其它插件，也不会删除文章、用户或附件。</span>
			</div>
			<form method="post" action="main.php" onsubmit="return confirm('确定要删除 AT8 PageSpeed 的所有配置吗？\n此操作不可恢复，所有自定义设置将恢复为默认值。');">
				<input type="hidden" name="act" value="reset">
				<input type="hidden" name="csrfToken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
				<button type="submit" class="at8ps-btn at8ps-btn-danger">清除插件配置</button>
			</form>
		</div>
	</div>
</div>
<script>if (typeof ActiveLeftMenu == "function") { ActiveLeftMenu("nav_at8_pagespeed"); }</script>
<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
