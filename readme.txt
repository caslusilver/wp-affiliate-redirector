=== WP Affiliate Redirector ===
Contributors: seuusuario
Tags: afiliados, redirect, cloaking, links
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gerenciador de links de afiliado com página intermediária (somente loader) e redirecionamento configurável.

== Description ==

O WP Affiliate Redirector permite criar links afiliados no painel usando um Custom Post Type.

Cada link possui:

- slug público permanente em /go/{slug}
- URL de destino editável (o slug nunca muda)

Ao acessar o slug público, o visitante vê uma página intermediária real (somente animação em CSS, sem texto) por ~3 segundos, e então é redirecionado automaticamente.

A página intermediária é marcada como noindex/nofollow para evitar indexação.

== Installation ==

1. Envie a pasta do plugin para /wp-content/plugins/
2. Ative em Plugins
3. Acesse Links Afiliado no menu do wp-admin e crie seus links

== Frequently Asked Questions ==

= Como uso o painel de gestão no front? =

Crie uma página e adicione o shortcode:

[war_link_manager]

Observação: o painel é restrito a administradores (manage_options) e usa AJAX via admin-ajax.php.

= Qual shortcode retorna a URL pública de um link? =

Use:

[war_link id="123"]

= Posso alterar o destino sem mudar a URL pública? =

Sim. O slug /go/{slug} permanece, e você altera somente a URL de destino no wp-admin.

= Esse plugin faz tracking de cliques? =

Não nesta versão. O tracking deve ser feito externamente (pixels Meta, Google etc.).

== Changelog ==

= 0.4.1 =
* Corrige empacotamento: inclui arquivo `inc/admin/class-qrcode-ajax.php` no release (evita fatal na ativação).

= 0.4.0 =
* Gerador de QR Code no painel do Link Manager.
* Suporte a Keywords nos links para resposta automática no Chat IG (modo local) com fallback para webhook.


