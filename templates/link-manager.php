<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="war-manager" data-war-manager="1" style="<?php echo isset($war_manager_style) ? esc_attr($war_manager_style) : ''; ?>">
	<div class="war-topbar">
		<div class="war-topbar__head">
			<div class="war-topbar__left">
				<span class="war-topbar__spacer" aria-hidden="true"></span>
			</div>
			<div class="war-topbar__title"><?php echo esc_html__('Affiliate Links', WAR_TEXT_DOMAIN); ?></div>
			<button type="button" class="war-actionlink" data-war-open-create="1"><?php echo esc_html__('Criar', WAR_TEXT_DOMAIN); ?></button>
		</div>

		<label class="war-search" aria-label="<?php echo esc_attr__('Buscar', WAR_TEXT_DOMAIN); ?>">
			<span class="war-search__icon" aria-hidden="true">🔎</span>
			<input class="war-search__input" type="search" placeholder="<?php echo esc_attr__('Buscar', WAR_TEXT_DOMAIN); ?>" data-war-search="1" />
		</label>
	</div>

	<div class="war-view" data-war-view="list">
		<div class="war-list" data-war-rows="1">
			<div class="war-list__empty"><?php echo esc_html__('Carregando...', WAR_TEXT_DOMAIN); ?></div>
		</div>

		<div class="war-footerbar">
			<div class="war-footerbar__left">
				<button type="button" class="war-btn" data-war-refresh="1"><?php echo esc_html__('Atualizar', WAR_TEXT_DOMAIN); ?></button>
			</div>
			<div class="war-footerbar__right" data-war-pagination="1"></div>
		</div>
	</div>

	<div class="war-view" data-war-view="create" hidden>
		<div class="war-panel">
			<div class="war-panel__head">
				<button type="button" class="war-actionlink" data-war-close-create="1"><?php echo esc_html__('Voltar', WAR_TEXT_DOMAIN); ?></button>
				<div class="war-panel__title" data-war-form-title="1"><?php echo esc_html__('Criar', WAR_TEXT_DOMAIN); ?></div>
				<button type="button" class="war-actionlink war-actionlink--right" data-war-submit-create="1"><?php echo esc_html__('Criar', WAR_TEXT_DOMAIN); ?></button>
			</div>

			<form class="war-form" data-war-form="1">
				<input type="hidden" name="id" value="" data-war-field="id" />

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('URL de destino', WAR_TEXT_DOMAIN); ?></div>
					<input class="war-field__input" type="url" name="destination" required placeholder="https://..." data-war-field="destination" />
				</label>

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Título', WAR_TEXT_DOMAIN); ?></div>
					<input class="war-field__input" type="text" name="title" required placeholder="<?php echo esc_attr__('Digite um título', WAR_TEXT_DOMAIN); ?>" data-war-field="title" />
				</label>

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Slug (opcional)', WAR_TEXT_DOMAIN); ?></div>
					<input class="war-field__input" type="text" name="slug" placeholder="<?php echo esc_attr__('Slug (gerado automaticamente)', WAR_TEXT_DOMAIN); ?>" data-war-field="slug" />
					<div class="war-field__hint"><?php echo esc_html__('O slug será sugerido pelo título, mas você pode editar.', WAR_TEXT_DOMAIN); ?></div>
				</label>

				<div class="war-form__actions">
					<button type="submit" class="war-btn war-btn--primary" data-war-action="submit" style="display:none;"><?php echo esc_html__('Criar', WAR_TEXT_DOMAIN); ?></button>
					<button type="button" class="war-btn" data-war-action="cancel" style="display:none;"><?php echo esc_html__('Cancelar edição', WAR_TEXT_DOMAIN); ?></button>
					<span class="war-form__status" data-war-status="1" aria-live="polite"></span>
				</div>
			</form>
		</div>
	</div>
</div>


