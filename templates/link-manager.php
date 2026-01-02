<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="war-manager" data-war-manager="1">
	<div class="war-topbar">
		<div class="war-topbar__left">
			<button type="button" class="war-topbar__menu" aria-label="<?php echo esc_attr__('Menu', WAR_TEXT_DOMAIN); ?>" disabled>≡</button>
			<div class="war-topbar__brand" aria-label="WAR">war</div>
		</div>
		<div class="war-topbar__center">
			<label class="war-search" aria-label="<?php echo esc_attr__('Buscar', WAR_TEXT_DOMAIN); ?>">
				<span class="war-search__icon" aria-hidden="true">🔎</span>
				<input class="war-search__input" type="search" placeholder="<?php echo esc_attr__('Search', WAR_TEXT_DOMAIN); ?>" data-war-search="1" />
			</label>
		</div>
		<div class="war-topbar__right">
			<button type="button" class="war-btn war-btn--primary" data-war-open-create="1"><?php echo esc_html__('Create', WAR_TEXT_DOMAIN); ?></button>
		</div>
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
				<div class="war-panel__title" data-war-form-title="1"><?php echo esc_html__('Create', WAR_TEXT_DOMAIN); ?></div>
				<button type="button" class="war-btn war-btn--link" data-war-close-create="1"><?php echo esc_html__('Cancel', WAR_TEXT_DOMAIN); ?></button>
			</div>

			<form class="war-form" data-war-form="1">
				<input type="hidden" name="id" value="" data-war-field="id" />

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Enter your destination URL', WAR_TEXT_DOMAIN); ?></div>
					<input class="war-field__input" type="url" name="destination" required placeholder="https://..." data-war-field="destination" />
				</label>

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Select domain (optional)', WAR_TEXT_DOMAIN); ?></div>
					<div class="war-field__readonly" data-war-domain="1"><?php echo esc_html(home_url('/go/')); ?></div>
				</label>

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Back-half (optional)', WAR_TEXT_DOMAIN); ?></div>
					<input class="war-field__input" type="text" name="slug" placeholder="<?php echo esc_attr__('Custom back-half (optional)', WAR_TEXT_DOMAIN); ?>" data-war-field="slug" />
					<div class="war-field__hint"><?php echo esc_html__('A parte customizável do seu link (ex.: /go/back-half). Deixe em branco para gerar automático.', WAR_TEXT_DOMAIN); ?></div>
				</label>

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Title (required)', WAR_TEXT_DOMAIN); ?></div>
					<input class="war-field__input" type="text" name="title" required placeholder="<?php echo esc_attr__('Tap to enter title', WAR_TEXT_DOMAIN); ?>" data-war-field="title" />
				</label>

				<div class="war-form__actions">
					<button type="submit" class="war-btn war-btn--primary" data-war-action="submit"><?php echo esc_html__('Create', WAR_TEXT_DOMAIN); ?></button>
					<button type="button" class="war-btn" data-war-action="cancel" style="display:none;"><?php echo esc_html__('Cancelar edição', WAR_TEXT_DOMAIN); ?></button>
					<span class="war-form__status" data-war-status="1" aria-live="polite"></span>
				</div>
			</form>
		</div>
	</div>
</div>


