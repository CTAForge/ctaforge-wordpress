# CTAForge — WordPress Plugin

Conecte seu site WordPress ao [CTAForge](https://ctaforge.com) e transforme visitantes em contatos de email marketing — com formulários de captura, sincronização automática e integração completa com WooCommerce.

---

## ⚡ Instalação rápida

### Opção 1 — WordPress.org (recomendado)

1. No painel do WordPress, acesse **Plugins → Adicionar novo**
2. Busque por **CTAForge**
3. Clique em **Instalar agora** → **Ativar**

### Opção 2 — Upload manual (ZIP)

1. Baixe o arquivo `ctaforge.zip` da [última versão](https://github.com/CTAForge/ctaforge-wordpress/releases/latest)
2. No painel do WordPress, acesse **Plugins → Adicionar novo → Enviar plugin**
3. Selecione o arquivo `.zip` baixado e clique em **Instalar agora**
4. Clique em **Ativar plugin**

---

## 🔑 Configuração

Após ativar o plugin:

1. Acesse **Configurações → CTAForge** no painel do WordPress
2. No CTAForge, vá em **Configurações → Integrações → + Nova integração WordPress**
3. Copie a chave API gerada (ela aparece apenas uma vez)
4. Cole a chave no campo **API Key** e clique em **Test Connection**
5. Se a conexão for bem-sucedida, selecione a **Lista padrão** e salve

---

## 📋 Formulário de captura

Adicione um formulário de inscrição em qualquer página, post ou widget usando o shortcode:

```
[ctaforge_form]
```

### Exemplos

Formulário básico (usa a lista padrão configurada):
```
[ctaforge_form]
```

Com título e lista específica:
```
[ctaforge_form list_id="SEU-UUID-AQUI" title="Receba novidades semanais" button="Quero me inscrever!"]
```

Com campos de nome:
```
[ctaforge_form fields="first_name,last_name" title="Fique por dentro"]
```

### Parâmetros disponíveis

| Parâmetro     | Descrição                                              | Padrão                        |
|---------------|--------------------------------------------------------|-------------------------------|
| `list_id`     | UUID da lista no CTAForge                              | Lista padrão das configurações |
| `title`       | Título exibido acima do formulário                     | "Subscribe to our newsletter" |
| `description` | Subtítulo opcional                                     | —                             |
| `button`      | Texto do botão de envio                                | "Subscribe"                   |
| `placeholder` | Placeholder do campo de email                          | "Your email address"          |
| `fields`      | Campos extras: `first_name`, `last_name`               | —                             |
| `success`     | Mensagem exibida após inscrição bem-sucedida           | "Thank you for subscribing!"  |
| `error`       | Mensagem exibida em caso de erro                       | "Something went wrong."       |
| `class`       | Classes CSS extras para o wrapper do formulário        | —                             |

---

## 🧱 Bloco Gutenberg

O plugin adiciona o bloco **CTAForge Signup Form** na categoria Widgets do editor de blocos.

- Arraste o bloco para qualquer posição na página
- Configure título, botão e lista na barra lateral direita
- A pré-visualização é exibida diretamente no editor

---

## 🛒 Integração com WooCommerce

Quando o WooCommerce está instalado, o CTAForge detecta automaticamente e ativa recursos adicionais:

| Evento                  | O que acontece no CTAForge                                  |
|-------------------------|-------------------------------------------------------------|
| Pedido realizado        | Contato adicionado à lista com a tag `woocommerce-customer` |
| Pedido concluído        | Tag `woocommerce-purchased` adicionada ao contato           |
| Pedido reembolsado      | Tag `woocommerce-refunded` adicionada ao contato            |

Todos os eventos ficam visíveis na **linha do tempo do contato** no CTAForge, permitindo segmentação avançada.

---

## 👤 Sincronização de usuários

Ative a opção **Sincronizar usuários WordPress** em Configurações para adicionar automaticamente novos usuários registrados à lista padrão.

---

## ❓ Perguntas frequentes

**Onde encontro minha chave API?**
No CTAForge: Configurações → Integrações → crie uma nova integração WordPress.

**O plugin funciona sem WooCommerce?**
Sim — formulários e sincronização de usuários funcionam normalmente. Os recursos de WooCommerce são ativados apenas quando o plugin está instalado.

**Posso usar múltiplos formulários na mesma página?**
Sim — cada shortcode `[ctaforge_form]` é independente e pode apontar para listas diferentes.

**O plugin é compatível com construtores de página?**
Sim — funciona com Elementor, Beaver Builder, Divi e qualquer construtor que suporte shortcodes WordPress.

---

## 📄 Licença

GPL v2 ou superior — veja [LICENSE](LICENSE).
