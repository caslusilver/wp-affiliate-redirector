<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Template do simulador de Direct (Instagram-like).
 *
 * Variáveis esperadas:
 * - $chat_header: array{name:string,status:string,avatar_url:string}
 * - $initial_message: string
 */

$chat_header = isset($chat_header) && is_array($chat_header) ? $chat_header : [];
$name = isset($chat_header['name']) ? (string) $chat_header['name'] : 'Chat';
$status = isset($chat_header['status']) ? (string) $chat_header['status'] : 'Online agora';
$avatar_url = isset($chat_header['avatar_url']) ? (string) $chat_header['avatar_url'] : '';
$initial_message = isset($initial_message) ? (string) $initial_message : '';
?>

<div class="war-ig-chat" data-war-ig-chat="1">
	<div class="war-ig-chat__panel">
		<!-- HEADER -->
		<div class="war-ig-header">
			<button type="button" class="war-ig-back" aria-label="<?php echo esc_attr__('Voltar', WAR_TEXT_DOMAIN); ?>" disabled>←</button>

			<div class="war-ig-avatar" aria-hidden="<?php echo $avatar_url ? 'false' : 'true'; ?>">
				<?php if ($avatar_url) : ?>
					<img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr__('Avatar', WAR_TEXT_DOMAIN); ?>">
				<?php endif; ?>
			</div>

			<div class="war-ig-title">
				<div class="war-ig-name"><?php echo esc_html($name); ?></div>
				<div class="war-ig-status"><?php echo esc_html($status); ?></div>
			</div>
		</div>

		<!-- MENSAGENS -->
		<div class="war-ig-messages" data-war-ig-messages="1" role="log" aria-live="polite" aria-relevant="additions">
			<?php if ($initial_message) : ?>
				<div class="war-ig-row war-ig-row--in">
					<div class="war-ig-bubble war-ig-bubble--in">
						<div class="war-ig-text"><?php echo nl2br(esc_html($initial_message)); ?></div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- INPUT -->
		<div class="war-ig-input">
			<input type="text" class="war-ig-input__field" data-war-ig-input="1" placeholder="<?php echo esc_attr__('Mensagem…', WAR_TEXT_DOMAIN); ?>" autocomplete="off" />
			<button type="button" class="war-ig-send" data-war-ig-send="1" aria-label="<?php echo esc_attr__('Enviar', WAR_TEXT_DOMAIN); ?>">
				<span class="war-ig-send__fallback" aria-hidden="true">➤</span>
			</button>
		</div>
	</div>
</div>


