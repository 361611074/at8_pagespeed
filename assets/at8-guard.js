/**
 * at8_pagespeed · 预加载守卫
 *
 * 1) 给命中黑名单关键字的链接打 data-no-instant 标记，instant.page 见标记即跳过预加载
 *    （退出登录、购物车、支付、删除等敏感操作不被提前请求）；
 *    instant.page v5 的判定依据（内置文件源码）：`if ('noInstant' in anchorElement.dataset) return`。
 * 2) 兜底写入 instant.page v5 的触发延迟（v5 从 document.body 的 data 属性读取）。
 *
 * 黑名单匹配采用三重比对，避免因大小写或 URL 编码差异被绕过：
 *   ① 原样 href 转小写；
 *   ② URL 解码后再转小写（拦 log%6Fut / %2Fadmin 之类编码写法）；
 *   ③ 浏览器归一化后的 path + search 转小写（拦相对路径 / 大小写不一致）。
 *
 * 代码风格：按官方《注意事项速查表》建议放弃 var，统一使用 let / const（需 ES6 环境）。
 */
(function () {
	'use strict';

	// ---- 兜底：确保 instant.page v5 能读到触发延迟 ----
	const delay = window.at8PsPreloadDelay;
	if (typeof delay === 'number' && isFinite(delay) && document.body
		&& !document.body.hasAttribute('data-instant-intensity')) {
		document.body.setAttribute('data-instant-intensity', String(delay));
	}

	const list = (window.at8PsBlacklist && typeof window.at8PsBlacklist === 'object'
		&& window.at8PsBlacklist.length) ? window.at8PsBlacklist : [];

	/** 安全解码：无 % 或解码失败时返回空串（调用方会跳过该重比对） */
	function decode(s) {
		if (s.indexOf('%') === -1) {
			return '';
		}
		try {
			return decodeURIComponent(s);
		} catch (e) {
			return '';
		}
	}

	/** 是否命中黑名单 */
	function hit(a) {
		const raw = a.getAttribute('href') || '';
		if (raw === '' || raw.charAt(0) === '#') {
			return false;
		}

		// 候选串：原样 / URL 解码后 / 浏览器归一化后的 path+search，全部转小写后比对
		const candidates = [
			raw.toLowerCase(),
			decode(raw).toLowerCase(),
			(typeof a.pathname === 'string')
				? (a.pathname + (a.search || '')).toLowerCase()
				: ''
		];

		for (let j = 0; j < list.length; j++) {
			const k = String(list[j]).toLowerCase();
			if (k === '') {
				continue;
			}
			for (let c = 0; c < candidates.length; c++) {
				if (candidates[c] !== '' && candidates[c].indexOf(k) !== -1) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * 祖先是否已声明排除预加载。
	 *
	 * instant.page v5 只读取 <a> 自身的 dataset.noInstant（内置文件源码实锤），
	 * 不支持容器级排除；这里补齐该能力——站点可在导航 / 挂件 / 评论区的容器上
	 * 写一次 data-no-instant，整块区域的链接都不再被预加载。
	 * 手动向上遍历（不依赖 Element.closest，兼容面更广）。
	 */
	function optedOutByAncestor(a) {
		let p = a.parentNode;
		while (p && p.nodeType === 1) {
			if (p.hasAttribute && p.hasAttribute('data-no-instant')) {
				return true;
			}
			p = p.parentNode;
		}
		return false;
	}

	function mark(a) {
		if (a.hasAttribute('data-no-instant')) {
			return;
		}
		if (optedOutByAncestor(a) || hit(a)) {
			a.setAttribute('data-no-instant', '');
		}
	}

	function guard(root) {
		if (!root || !root.querySelectorAll) {
			return;
		}
		const anchors = root.querySelectorAll('a[href]');
		for (let i = 0; i < anchors.length; i++) {
			mark(anchors[i]);
		}
	}

	function run() {
		guard(document);

		if (!list.length || typeof MutationObserver === 'undefined') {
			return;
		}

		// 动态插入的链接（评论翻页、无限滚动等）兜底监听：
		// 只扫描新增节点自身，避免每次变更都全文档重扫。
		const mo = new MutationObserver(function (muts) {
			for (let k = 0; k < muts.length; k++) {
				const nodes = muts[k].addedNodes;
				for (let n = 0; n < nodes.length; n++) {
					const node = nodes[n];
					if (!node || node.nodeType !== 1) {
						continue;
					}
					if (node.tagName === 'A') {
						mark(node);
					} else if (node.querySelectorAll) {
						guard(node);
					}
				}
			}
		});
		mo.observe(document.documentElement, { childList: true, subtree: true });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
})();
