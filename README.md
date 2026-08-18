# Smart Shop AI Assistant

AI-powered shopping assistant plugin for WordPress and WooCommerce. Helps customers find products through natural language chat.

## Features (MVP)

- **AI Chatbot** — Floating chat widget with natural language product search
- **Smart Search** — Converts user queries to structured attributes (vehicle, size, color, etc.)
- **Normal Search** — Standard WooCommerce text search fallback
- **Product Ranking** — Match score based on vehicle, size, color, brand, stock
- **Provider Agnostic AI** — OpenAI, Anthropic, Gemini, OpenAI-Compatible APIs
- **MCP Ready** — WooCommerce Direct (built-in) or external WooCommerce MCP
- **Dynamic Attributes** — Auto-discovers WooCommerce global attributes with mapping UI
- **AI Rules** — Configurable rules with priority and active/inactive toggle
- **System Prompt** — Editable global system prompt
- **Capabilities** — Toggle what AI can do (search, stock check, add to cart, etc.)
- **Conversation Logs** — Debug logs with intent, search query, products found
- **Diagnostics** — System health checks and test query runner
- **Conversation Context** — Multi-turn conversation support

## Requirements

- WordPress 6.0+
- PHP 7.4+
- WooCommerce 7.0+

## Installation

1. Copy the `smart-shop-ai` folder to `wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. Go to **Smart Shop AI** in the admin menu
4. Configure AI Provider (API key, model, endpoint)
5. Map WooCommerce attributes (Vehicle, Wheel Size, Color, etc.)
6. Review AI Rules and System Prompt
7. Run Diagnostics to verify everything works

## Quick Setup

1. **AI Provider** → Set API key and model (e.g. `gpt-4o-mini`)
2. **MCP Settings** → Use "WooCommerce Direct" (default) or connect external MCP
3. **Product Search** → Map attributes: `vehicle` → `pa_vehicle`, `wheel_size` → `pa_wheel-size`, etc.
4. **AI Rules** → Review default rules (size followup, stock filter, etc.)
5. **Diagnostics** → Run test query: `برای پژو 206 رینگ 16 میخوام`

## Architecture

```
smart-shop-ai/
├── smart-shop-ai.php          # Plugin bootstrap
├── includes/
│   ├── Core/                  # Plugin, Settings, Logger, Activator
│   ├── AI/                    # Providers, AIService, IntentParser
│   ├── MCP/                   # MCP providers, MCPService
│   ├── WooCommerce/           # AttributeDiscovery, ProductSearcher
│   ├── Search/                # SmartSearch, NormalSearch, SearchRouter
│   ├── Recommendation/        # ProductRanker
│   ├── Rules/                 # RulesManager
│   ├── REST/                  # Chat, Settings, Diagnostics controllers
│   ├── Admin/                 # Admin menu
│   └── Frontend/              # Chatbot loader
├── admin/                     # Admin CSS/JS
├── frontend/                  # Chatbot CSS/JS
└── templates/                 # Admin and frontend templates
```

## REST API

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/smart-shop-ai/v1/chat` | POST | Send chat message |
| `/smart-shop-ai/v1/chat/config` | GET | Get chatbot config |
| `/smart-shop-ai/v1/settings/ai` | GET/POST | AI provider settings |
| `/smart-shop-ai/v1/settings/mcp` | GET/POST | MCP settings |
| `/smart-shop-ai/v1/settings/prompt` | GET/POST | System prompt |
| `/smart-shop-ai/v1/settings/capabilities` | GET/POST | AI capabilities |
| `/smart-shop-ai/v1/settings/attributes` | GET/POST | Attribute mapping |
| `/smart-shop-ai/v1/rules` | GET/POST | AI rules CRUD |
| `/smart-shop-ai/v1/logs` | GET | Conversation logs |
| `/smart-shop-ai/v1/diagnostics` | GET | System diagnostics |
| `/smart-shop-ai/v1/diagnostics/test` | POST | Run test query |
| `/smart-shop-ai/v1/test/ai` | POST | Test AI connection |
| `/smart-shop-ai/v1/test/mcp` | POST | Test MCP connection |

## Test Scenarios

| Query | Expected Behavior |
|-------|-------------------|
| `برای پژو 206 رینگ میخوام` | Detect wheel intent, ask about size |
| `برای پژو 206 رینگ 16 میخوام` | Find compatible products |
| `رینگ اسپرت مشکی سایز 17 میخوام` | Search by attributes |
| `رینگ مدل X رو میخوام` | Normal product search |
| `باتری مناسب پژو 405 میخوام` | Change product type to battery |
| `ارزون‌ترین رینگ مناسب 206` | Find and rank by price |

## Security

- Nonce verification on REST API
- Capability checks (`manage_options`) on admin endpoints
- Input sanitization and output escaping
- API keys masked in admin UI
- Sensitive data excluded from conversation logs

## License

GPL-2.0+
