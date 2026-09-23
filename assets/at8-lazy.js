/**
 * at8_pagespeed · 图片 / iframe 懒加载
 *
 * 无依赖、无外站资源。给「不在当前视口内」的图片与 iframe 补 loading="lazy"
 * （图片另加 decoding="async"），由浏览器原生懒加载接管后续调度。
 *
 * 保守原则——只处理有把握的元素，作者已表态的一律不动：
 *   · 已有 loading 属性：不动（作者已指定 eager/lazy）；
 *   · 已有 fetchpriority 属性：不动（作者已给出优先级提示，如 LCP 大图）；
 *   · 元素自身或任一祖先带 data-no-lazy：不动；
 *   · 不可见元素（display:none 的模板 / 轮播备用图，rect 全为 0）：不动，无法判断其视口位置；
 *   · 已在视口内（含与视口相交）：不动，避免拖慢首屏；
 *   · 前 N 张图片（可后台配置）：不动，保首屏大图 LCP。
 *
 * 实现采用「先测量、后写入」两趟处理：第一趟只读 getBoundingClientRect()，
 * 第二趟统一写属性，避免边读边写触发多次强制重排。
 *
 * 已知局限：本脚本在解析完成后执行，由前端脚本在加载后动态插入的图片不会被处理
 * （不做全文档 MutationObserver，以免在动态页面上产生不可预期的属性写入）。
 */
(function () {
	'use strict';

	var SKIP = (typeof window.at8PsLazySkip === 'number') ? window.at8PsLazySkip : 2;
	SKIP = Math.max(0, Math.min(20, SKIP));

	/** 沿祖先链查找 data-no-lazy（不依赖 Element.closest，兼容更广） */
	function inNoLazy(el) {
		var n = el;
		while (n && n.nodeType === 1) {
			if (n.hasAttribute && n.hasAttribute('data-no-lazy')) {
				return true;
			}
			n = n.parentNode;
		}
		return false;
	}

	function eligible(el) {
		if (el.hasAttribute('data-no-lazy')) {
			return false;
		}
		if (el.hasAttribute('loading')) {
			return false;
		}
		if (el.hasAttribute('fetchpriority')) {
			return false;
		}
		return !inNoLazy(el.parentNode);
	}

	function apply() {
		var media = document.querySelectorAll('img, iframe');
		var viewportH = window.innerHeight || document.documentElement.clientHeight || 0;
		var plan = [];
		var seen = 0;
		var i, el, r;

		// ---- 第一趟：只读测量，不写任何属性 ----
		for (i = 0; i < media.length; i++) {
			el = media[i];
			if (!eligible(el)) {
				continue;
			}

			r = el.getBoundingClientRect();

			// 未渲染 / display:none：尺寸为 0，位置无意义，保持原状
			if (r.width === 0 && r.height === 0) {
				continue;
			}

			if (el.tagName === 'IMG') {
				seen++;
				if (seen <= SKIP) {
					continue;
				}
			}

			// 与视口相交（含部分可见）：不懒加载
			if (r.top < viewportH && r.bottom > 0) {
				continue;
			}

			plan.push(el);
		}

		// ---- 第二趟：统一写入 ----
		for (i = 0; i < plan.length; i++) {
			el = plan[i];
			el.setAttribute('loading', 'lazy');
			if (el.tagName === 'IMG') {
				el.setAttribute('decoding', 'async');
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', apply);
	} else {
		apply();
	}
})();
