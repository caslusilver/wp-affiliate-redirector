# Guia MVP - Sistema de Listas de Afiliados

## 📋 Visão Geral

Este MVP adiciona ao plugin **WP Affiliate Redirector** um sistema completo de listas de produtos afiliados com imagens, visualizações flexíveis (lista/cards) e gerenciamento simplificado.

## ✨ Funcionalidades Implementadas

### 1. **Imagem Global por Link**
- Cada link de afiliado (`war_link`) agora pode ter uma imagem/foto associada
- Campo de upload integrado com a Media Library do WordPress
- A imagem aparece em qualquer lugar onde o link for exibido

### 2. **Taxonomia de Listas**
- Nova taxonomia `war_list` para agrupar links
- Cadastre listas como: "Lista de Compra Thermobox", "Materiais dos Alunos", etc.
- Um link pode pertencer a múltiplas listas

### 3. **Shortcode `[war_affiliate_list]`**
- Exibe links de uma lista específica em qualquer página/post
- Sintaxe: `[war_affiliate_list slug="nome-da-lista" view="list"]`
- Parâmetros:
  - `slug`: slug da lista (obrigatório)
  - `view`: "list" ou "card" (opcional, padrão: "list")

### 4. **Visualizações Flexíveis**
- **Lista**: Layout horizontal com foto pequena à esquerda
- **Cards**: Layout em grade com fotos grandes
- Alternância instantânea com botões de toggle
- Preferência salva no localStorage do navegador

### 5. **Modal de Zoom**
- Clique em qualquer imagem para visualizar em tamanho maior
- Modal elegante com fundo escurecido
- Fechar com ESC ou clique fora

### 6. **Modo Admin vs Visitante**
- **Visitantes**: Veem lista limpa com botões de "Acessar" e "Copiar"
- **Administradores**: Veem controles extras:
  - Botão de editar (abre admin em nova aba)
  - Botão de deletar (com confirmação)
  - Drag handle para reordenar itens
  - Reordenação salva automaticamente via AJAX

---

## 🚀 Como Usar

### **Passo 1: Criar uma Lista**

1. No admin do WordPress, vá em **Links Afiliado > Listas**
2. Clique em **"Adicionar Nova Lista"**
3. Digite o nome da lista (ex: "Lista de Compra Thermobox")
4. O slug será gerado automaticamente
5. Salve a lista

### **Passo 2: Adicionar Imagem e Lista aos Links**

1. Vá em **Links Afiliado > Todos os Links**
2. Edite um link existente ou crie um novo
3. Na sidebar direita, você verá:
   - **Imagem do Produto**: Clique em "Selecionar Imagem" e escolha da biblioteca
   - **Listas**: Marque as listas às quais este link pertence
4. Salve o link

### **Passo 3: Exibir a Lista em uma Página**

1. Crie ou edite uma página/post
2. Adicione o shortcode:
   ```
   [war_affiliate_list slug="lista-de-compra-thermobox"]
   ```
3. Ou com visualização padrão em cards:
   ```
   [war_affiliate_list slug="lista-de-compra-thermobox" view="card"]
   ```
4. Publique a página

---

## 🎨 Layouts Disponíveis

### **Visualização Lista (Padrão)**
```
[ foto ] Nome do produto
         af.link/produto
         [Acessar] [Copiar]
         (admin: [Editar] [Excluir] [☰])
```

**Ideal para:**
- Listas de referência rápida
- Muitos produtos
- Foco em títulos e links

### **Visualização Cards**
```
┌─────────────────┐
│                 │
│  foto grande    │
│                 │
├─────────────────┤
│ Nome do produto │
│ af.link/produto │
│ [Acessar]       │
│ [Copiar]        │
└─────────────────┘
```

**Ideal para:**
- Catálogos visuais
- Produtos com fotos atrativas
- Landing pages

---

## 🔧 Recursos Técnicos

### **Estrutura de Dados**
- **CPT**: `war_link` (já existente)
- **Taxonomia**: `war_list` (nova)
- **Meta Fields**:
  - `war_image_url`: URL da imagem do produto
  - `war_redirect_url`: URL de destino (já existente)
  - `war_keywords`: Keywords do chat IG (já existente)
- **Ordenação**: `menu_order` (drag and drop)

### **Endpoints AJAX**
- `war_links_list`: Lista links (atualizado com image_url e listas)
- `war_links_create`: Criar link (atualizado)
- `war_links_update`: Atualizar link (atualizado)
- `war_links_delete`: Deletar link (já existente)
- `war_links_save_order`: Salvar ordem (novo)
- `war_get_lists`: Obter todas as listas (novo)
- `war_list_get_links`: Obter links de uma lista (público/novo)

### **Assets**
- CSS: `assets/css/affiliate-list.css`
- JS: `assets/js/affiliate-list.js`
- jQuery UI Sortable (enqueue automático para admins)

---

## 📱 Responsividade

O sistema é totalmente responsivo:
- Desktop: Layouts otimizados
- Tablet: Ajustes em grid
- Mobile: Layout empilhado em coluna única

---

## 🎯 Exemplos de Uso

### **Lista de Produtos de um Curso**
```
[war_affiliate_list slug="produtos-curso-thermobox"]
```
Alunos veem os produtos recomendados com fotos e links diretos.

### **Catálogo de Ferramentas**
```
[war_affiliate_list slug="ferramentas-recomendadas" view="card"]
```
Exibição em cards para visual mais atrativo.

### **Material de Aula**
```
[war_affiliate_list slug="material-aula-01"]
```
Lista simples com links para download/compra.

---

## 🔄 Workflow Recomendado

1. **Cadastre suas listas** (categorias de produtos)
2. **Adicione imagens aos links** existentes via Media Library
3. **Associe links às listas** correspondentes
4. **Insira shortcodes** nas páginas onde quer exibir
5. **Reordene visualmente** (drag and drop) quando necessário
6. **Visitantes acessam** e copiam links facilmente

---

## 🚫 Funcionalidades Deixadas para Segunda Fase

Conforme planejado no MVP, as seguintes funcionalidades foram **intencionalmente deixadas de fora** para manter a simplicidade:

- ❌ Listas privadas/protegidas por senha
- ❌ Múltiplos links por produto (ML, Amazon, Shopee)
- ❌ Campo de preço
- ❌ Permissões granulares por lista
- ❌ Analytics detalhado por lista/plataforma
- ❌ Criação de links inline na página da lista
- ❌ Editor WYSIWYG integrado

Essas funcionalidades podem ser adicionadas em versões futuras conforme a demanda.

---

## 💡 Dicas e Boas Práticas

1. **Nomeie listas de forma clara**: Use nomes descritivos como "Ferramentas Essenciais" em vez de "Lista 1"

2. **Use imagens otimizadas**: Recomenda-se 800x800px para melhor visualização

3. **Organize por contexto**: Agrupe produtos que fazem sentido juntos (curso, categoria, evento)

4. **Teste ambas as visualizações**: Alguns produtos ficam melhores em lista, outros em cards

5. **Reordene estrategicamente**: Coloque os produtos mais importantes no topo

6. **Links curtos e memoráveis**: Use slugs simples no campo "Slug" dos links

---

## 🆘 Solução de Problemas

### **Shortcode não aparece**
- Verifique se o slug da lista está correto
- Confirme que existem links publicados naquela lista
- Limpe o cache do WordPress/tema

### **Imagens não carregam**
- Verifique se a URL da imagem está correta
- Confirme permissões de mídia no WordPress
- Teste com imagem diferente

### **Drag and drop não funciona**
- Confirme que está logado como administrador
- Verifique console do navegador para erros JS
- jQuery UI Sortable pode conflitar com alguns temas

### **Ordem não salva**
- Verifique nonce AJAX no console
- Confirme que admin-ajax.php está acessível
- Tente recarregar a página

---

## 📦 Arquivos Modificados/Criados

### **Novos Arquivos**
- `inc/frontend/class-affiliate-list.php`
- `assets/css/affiliate-list.css`
- `assets/js/affiliate-list.js`
- `docs/LISTAS-MVP-GUIDE.md` (este arquivo)

### **Arquivos Modificados**
- `inc/cpt-affiliate-link.php` (+ taxonomia war_list)
- `inc/meta-redirect-url.php` (+ campo de imagem)
- `inc/frontend/class-ajax-links.php` (+ suporte a listas e imagem)
- `wp-affiliate-redirector.php` (+ require da nova classe)

---

## 🎉 Conclusão

Este MVP entrega as funcionalidades essenciais para criar e gerenciar listas visuais de produtos afiliados de forma simples e eficiente. O sistema é extensível e pode receber novas funcionalidades conforme a necessidade.

**Versão do MVP**: 1.0  
**Data**: Janeiro 2026  
**Compatibilidade**: WordPress 5.8+, PHP 7.4+
