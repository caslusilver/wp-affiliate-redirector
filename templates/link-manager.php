<?php
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="war-manager" data-war-manager="1">
	<div class="war-manager__header">
		<h2 class="war-manager__title"><?php echo esc_html__('Gerenciador de Links (WAR)', WAR_TEXT_DOMAIN); ?></h2>
		<p class="war-manager__subtitle"><?php echo esc_html__('Crie, edite e delete slugs /go/{slug} e seus destinos.', WAR_TEXT_DOMAIN); ?></p>
	</div>

	<div class="war-manager__panel">
		<form class="war-form" data-war-form="1">
			<input type="hidden" name="id" value="" data-war-field="id" />

			<div class="war-form__row">
				<label class="war-form__label">
					<?php echo esc_html__('Título', WAR_TEXT_DOMAIN); ?>
					<input class="war-form__input" type="text" name="title" required placeholder="Ex.: Oferta Produto X" data-war-field="title" />
				</label>

				<label class="war-form__label">
					<?php echo esc_html__('Slug (somente na criação)', WAR_TEXT_DOMAIN); ?>
					<input class="war-form__input" type="text" name="slug" placeholder="ex: produto-x" data-war-field="slug" />
				</label>
			</div>

			<div class="war-form__row">
				<label class="war-form__label war-form__label--full">
					<?php echo esc_html__('URL de destino (http/https)', WAR_TEXT_DOMAIN); ?>
					<input class="war-form__input" type="url" name="destination" required placeholder="https://exemplo.com/oferta" data-war-field="destination" />
				</label>
			</div>

			<div class="war-form__actions">
				<button type="submit" class="button button-primary" data-war-action="submit"><?php echo esc_html__('Criar', WAR_TEXT_DOMAIN); ?></button>
				<button type="button" class="button" data-war-action="cancel" style="display:none;"><?php echo esc_html__('Cancelar edição', WAR_TEXT_DOMAIN); ?></button>
				<span class="war-form__status" data-war-status="1" aria-live="polite"></span>
			</div>
		</form>
	</div>

	<div class="war-manager__panel">
		<div class="war-toolbar">
			<div class="war-toolbar__left">
				<label class="war-toolbar__label">
					<?php echo esc_html__('Buscar', WAR_TEXT_DOMAIN); ?>
					<input class="war-toolbar__input" type="search" placeholder="Título ou slug" data-war-search="1" />
				</label>
			</div>
			<div class="war-toolbar__right">
				<button type="button" class="button" data-war-refresh="1"><?php echo esc_html__('Atualizar lista', WAR_TEXT_DOMAIN); ?></button>
			</div>
		</div>

		<div class="war-table-wrap">
			<table class="war-table">
				<thead>
					<tr>
						<th><?php echo esc_html__('Título', WAR_TEXT_DOMAIN); ?></th>
						<th><?php echo esc_html__('URL pública', WAR_TEXT_DOMAIN); ?></th>
						<th><?php echo esc_html__('Destino', WAR_TEXT_DOMAIN); ?></th>
						<th><?php echo esc_html__('Ações', WAR_TEXT_DOMAIN); ?></th>
					</tr>
				</thead>
				<tbody data-war-rows="1">
					<tr><td colspan="4"><?php echo esc_html__('Carregando...', WAR_TEXT_DOMAIN); ?></td></tr>
				</tbody>
			</table>
		</div>

		<div class="war-pagination" data-war-pagination="1"></div>
	</div>
</div>


