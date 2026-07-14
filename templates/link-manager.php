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
			<button type="button" class="war-actionlink war-actionlink--plus" aria-label="<?php echo esc_attr__('Criar', WAR_TEXT_DOMAIN); ?>" data-war-open-create="1">+</button>
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
				<div class="war-panel__title" data-war-form-title="1"><?php echo esc_html__('Criar', WAR_TEXT_DOMAIN); ?></div>
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

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Keywords (Chat IG)', WAR_TEXT_DOMAIN); ?></div>
					<textarea class="war-field__input" name="keywords" rows="3" placeholder="<?php echo esc_attr__('pdf, ebook, baixar, download', WAR_TEXT_DOMAIN); ?>" data-war-field="keywords"></textarea>
					<div class="war-field__hint"><?php echo esc_html__('Separe as palavras por vírgula. Não diferencia maiúsculas/minúsculas.', WAR_TEXT_DOMAIN); ?></div>
				</label>

				<div class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Imagem do Produto', WAR_TEXT_DOMAIN); ?></div>
					<div class="war-image-upload" data-war-image-upload="1">
						<div class="war-image-preview" data-war-image-preview="1" style="margin-bottom:12px;min-height:120px;border:2px dashed #ddd;border-radius:8px;background:#f9f9f9;display:flex;align-items:center;justify-content:center;">
							<div class="war-image-placeholder" style="text-align:center;color:#999;padding:20px;">
								<div style="font-size:48px;margin-bottom:8px;">📷</div>
								<div><?php echo esc_html__('Nenhuma imagem selecionada', WAR_TEXT_DOMAIN); ?></div>
							</div>
						</div>
						<input type="hidden" name="image_url" data-war-field="image_url" data-war-image-input="1" />
						<div style="display:flex;gap:8px;">
							<button type="button" class="war-btn war-btn--secondary" data-war-select-image="1" style="flex:1;">
								<?php echo esc_html__('Selecionar Imagem', WAR_TEXT_DOMAIN); ?>
							</button>
							<button type="button" class="war-btn" data-war-remove-image="1" style="flex:1;display:none;">
								<?php echo esc_html__('Remover', WAR_TEXT_DOMAIN); ?>
							</button>
						</div>
					</div>
				</div>

				<label class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Descrição', WAR_TEXT_DOMAIN); ?></div>
					<textarea class="war-field__input" name="description" rows="4" placeholder="<?php echo esc_attr__('Breve descrição do produto...', WAR_TEXT_DOMAIN); ?>" data-war-field="description"></textarea>
					<div class="war-field__hint"><?php echo esc_html__('Aparecerá nas listas/cards. Se longa, mostrará "Ver mais".', WAR_TEXT_DOMAIN); ?></div>
				</label>

				<div class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Listas', WAR_TEXT_DOMAIN); ?></div>
					<div class="war-lists-checkboxes" data-war-lists-container="1" style="max-height:200px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;padding:12px;background:#fff;">
						<div style="color:#999;font-style:italic;"><?php echo esc_html__('Carregando listas...', WAR_TEXT_DOMAIN); ?></div>
					</div>
					<div class="war-field__hint"><?php echo esc_html__('Selecione as listas onde este link aparecerá.', WAR_TEXT_DOMAIN); ?></div>
				</div>

				<div class="war-field">
					<div class="war-field__label"><?php echo esc_html__('Botões de Destino', WAR_TEXT_DOMAIN); ?></div>
					<div class="war-buttons-editor" data-war-buttons-editor="1">
						<div class="war-buttons-list" data-war-buttons-list="1" style="margin-bottom:12px;">
							<div class="war-no-buttons" style="color:#999;font-style:italic;padding:12px;background:#f9f9f9;border-radius:4px;">
								<?php echo esc_html__('Nenhum botão configurado. Será usado o campo "URL de destino" principal.', WAR_TEXT_DOMAIN); ?>
							</div>
						</div>
						<button type="button" class="war-btn war-btn--secondary" data-war-add-button="1">
							<?php echo esc_html__('+ Adicionar Botão', WAR_TEXT_DOMAIN); ?>
						</button>
					</div>
					<div class="war-field__hint"><?php echo esc_html__('Configure múltiplos botões com labels personalizadas.', WAR_TEXT_DOMAIN); ?></div>
				</div>

				<div class="war-form__actions">
					<button type="button" class="war-btn" data-war-close-create="1"><?php echo esc_html__('Voltar', WAR_TEXT_DOMAIN); ?></button>
					<button type="submit" class="war-btn war-btn--primary" data-war-action="submit"><?php echo esc_html__('Criar', WAR_TEXT_DOMAIN); ?></button>
					<button type="button" class="war-btn" data-war-action="cancel" style="display:none;"><?php echo esc_html__('Cancelar edição', WAR_TEXT_DOMAIN); ?></button>
					<span class="war-form__status" data-war-status="1" aria-live="polite"></span>
				</div>
			</form>
		</div>
	</div>
</div>


