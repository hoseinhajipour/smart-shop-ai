<?php
/**
 * Chatbot widget template.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="ssai-chatbot-root" class="ssai-chatbot-root" dir="rtl">
	<button id="ssai-chat-toggle" class="ssai-chat-toggle" aria-label="باز کردن چت">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
		</svg>
	</button>

	<div id="ssai-chat-window" class="ssai-chat-window" hidden>
		<div class="ssai-chat-header">
			<div class="ssai-chat-header-info">
				<span class="ssai-chat-avatar">🤖</span>
				<div>
					<strong>دستیار خرید</strong>
					<span class="ssai-chat-status">آنلاین</span>
				</div>
			</div>
			<button id="ssai-chat-close" class="ssai-chat-close" aria-label="بستن">&times;</button>
		</div>

		<div id="ssai-chat-messages" class="ssai-chat-messages"></div>

		<div id="ssai-quick-actions" class="ssai-quick-actions"></div>

		<div class="ssai-chat-input-area">
			<input type="text" id="ssai-chat-input" class="ssai-chat-input" placeholder="سؤال خود را بنویسید..." autocomplete="off" />
			<button id="ssai-chat-send" class="ssai-chat-send" aria-label="ارسال">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
					<path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
				</svg>
			</button>
		</div>
	</div>
</div>
