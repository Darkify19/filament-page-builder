<?php

namespace CarlJanzell\FilamentPageBuilder;

use CarlJanzell\FilamentPageBuilder\Blocks\ButtonBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\CodeBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\DividerBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\EmbedBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\HeadingBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\ImageBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\ListBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\QuoteBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\SectionBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\ShortcodeBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\SpacerBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\TextBlock;
use CarlJanzell\FilamentPageBuilder\Blocks\VideoBlock;
use CarlJanzell\FilamentPageBuilder\Contracts\PageBlock;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Throwable;

class FilamentPageBuilderPlugin implements Plugin
{
    /**
     * @var array<int, class-string<PageBlock>>
     */
    protected array $blocks = [];

    protected ?string $recordModel = null;

    protected string $blocksAttribute = 'blocks';

    protected ?string $canvasStylesView = null;

    protected bool $includeLayoutBlocks = true;

    /**
     * Constrained style knobs, keyed by token name.
     *
     * @var array<string, array<int|string, string>>
     */
    protected array $styleTokens = [];

    protected ?string $panelId = null;

    protected bool|Closure $allowCode = false;

    protected bool $customStyles = true;

    /**
     * @var array<string, array{callback: callable, description: ?string, example: ?string}>
     */
    protected array $shortcodes = [];

    /**
     * @var array<int, string>
     */
    protected array $embedHosts = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'page-builder';
    }

    /**
     * Block types this application makes available to editors.
     *
     * @param  array<int, class-string<PageBlock>>  $blocks
     */
    public function blocks(array $blocks): static
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * Optional model class name, for documentation and future artisan scaffolding.
     *
     * The canvas does not read this: it edits whichever record the DesignPage
     * resource bound. Kept as a fluent so existing calls stay valid.
     *
     * @param  class-string  $model
     */
    public function recordModel(string $model): static
    {
        $this->recordModel = $model;

        return $this;
    }

    /**
     * The JSON attribute on that model holding the ordered blocks.
     */
    public function blocksAttribute(string $attribute): static
    {
        $this->blocksAttribute = $attribute;

        return $this;
    }

    /**
     * A view rendered inside the canvas, before the blocks.
     *
     * The package styles the builder chrome but knows nothing about how a consuming
     * application styles its own blocks. This is where the application injects its
     * design tokens and block stylesheet so the canvas matches the public site.
     */
    public function canvasStylesView(?string $view): static
    {
        $this->canvasStylesView = $view;

        return $this;
    }

    public function getCanvasStylesView(): ?string
    {
        return $this->canvasStylesView;
    }

    /**
     * Whether the package's own layout primitives appear in the palette.
     *
     * On by default so a new panel already has sections, text boxes and spacers.
     * Registering a block with the same type() replaces the shipped
     * one. Pass false to keep a palette of only the application's own types.
     */
    public function includeLayoutBlocks(bool $include = true): static
    {
        $this->includeLayoutBlocks = $include;

        return $this;
    }

    /**
     * Who may add and edit Custom code blocks (HTML, CSS and JavaScript).
     *
     * Off by default. A code block's script runs on the public page with the visitor's
     * full trust — on the same origin as this panel — so opening it to every editor is
     * the application's decision to make, not the package's. Pass a closure to decide per
     * request, typically a role check. Existing code blocks keep rendering either way.
     */
    public function allowCode(bool|Closure $condition = true): static
    {
        $this->allowCode = $condition;

        return $this;
    }

    public function canUseCode(): bool
    {
        return (bool) value($this->allowCode);
    }

    /**
     * Whether the inspector offers free-form styles (spacing, colour, background, size)
     * beside the token presets. On by default; pass false to keep editors to the tokens.
     */
    public function customStyles(bool $enabled = true): static
    {
        $this->customStyles = $enabled;

        return $this;
    }

    public function hasCustomStyles(): bool
    {
        return $this->customStyles;
    }

    /**
     * Register a `[name]` shortcode that pages on this panel can call.
     *
     * The callback receives the shortcode's attributes (`[news count="3"]` →
     * `['count' => '3']`) and the enclosed content of `[name]…[/name]`, and returns
     * markup: a string, an Htmlable or a View. Its output is trusted, so escape anything
     * it echoes back from `$attributes`.
     *
     * @param  callable(array<int|string, string>, ?string): mixed  $callback
     */
    public function shortcode(string $name, callable $callback, ?string $description = null, ?string $example = null): static
    {
        $this->shortcodes[$name] = [
            'callback' => $callback,
            'description' => $description,
            'example' => $example,
        ];

        return $this;
    }

    /**
     * Extra hosts the Embed block may frame, on top of the shipped allowlist.
     *
     * A host allows its subdomains too. Each one is a site you are letting run inside
     * your pages, so list only the ones you mean.
     *
     * @param  array<int, string>  $hosts
     */
    public function embedHosts(array $hosts): static
    {
        $this->embedHosts = [...$this->embedHosts, ...$hosts];

        return $this;
    }

    /**
     * Token sets the style inspector offers.
     *
     * Values are stored as names, not CSS, and emitted as `data-fpb-{token}` on the
     * block wrapper. These are the brand presets: an editor picking from them cannot
     * produce a 13px lime heading. Exact values — pixels, a colour from a picker — live
     * in the separate custom style layer, which `customStyles(false)` turns off.
     *
     * @param  array<string, array<int|string, string>>  $tokens
     */
    public function styleTokens(array $tokens): static
    {
        $this->styleTokens = $tokens;

        return $this;
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    public function getStyleTokens(): array
    {
        return $this->styleTokens !== [] ? $this->styleTokens : static::defaultStyleTokens();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function defaultStyleTokens(): array
    {
        return [
            'padding' => [
                'none' => 'None',
                'sm' => 'Small',
                'md' => 'Medium',
                'lg' => 'Large',
                'xl' => 'Extra large',
            ],
            'background' => [
                'none' => 'None',
                'surface' => 'Surface',
                'muted' => 'Muted',
                'contrast' => 'Contrast',
            ],
            'width' => [
                'narrow' => 'Narrow',
                'default' => 'Default',
                'wide' => 'Wide',
                'full' => 'Full',
            ],
            'align' => [
                'start' => 'Start',
                'center' => 'Center',
                'end' => 'End',
            ],
        ];
    }

    /**
     * Layout and content primitives the package ships.
     *
     * @return array<int, class-string<PageBlock>>
     */
    public static function layoutBlockClasses(): array
    {
        return [
            SectionBlock::class,
            HeadingBlock::class,
            TextBlock::class,
            ListBlock::class,
            QuoteBlock::class,
            ImageBlock::class,
            ButtonBlock::class,
            VideoBlock::class,
            EmbedBlock::class,
            SpacerBlock::class,
            DividerBlock::class,
            ShortcodeBlock::class,
            CodeBlock::class,
        ];
    }

    /**
     * The configured attribute, resolvable from outside a panel.
     *
     * Models carrying blocks are read on the public site too, where no panel is current
     * and `filament()` would throw. Falling back to the default keeps a page rendering
     * rather than failing on a lookup it only needed for a name.
     */
    public static function configuredBlocksAttribute(string $default = 'blocks'): string
    {
        try {
            return static::get()->getBlocksAttribute();
        } catch (Throwable) {
            return $default;
        }
    }

    public function getRecordModel(): ?string
    {
        return $this->recordModel;
    }

    public function getBlocksAttribute(): string
    {
        return $this->blocksAttribute;
    }

    /**
     * The registry for the panel this plugin instance was registered on.
     *
     * Before registration there is no panel to speak of, so it falls back to whichever
     * one is current — which is what a caller outside a panel lifecycle means anyway.
     */
    public function getRegistry(): BlockRegistry
    {
        $registries = app(BlockRegistries::class);

        return $this->panelId === null
            ? $registries->current()
            : $registries->for($this->panelId);
    }

    public function register(Panel $panel): void
    {
        $this->panelId = $panel->getId();

        $blocks = $this->includeLayoutBlocks
            ? [...static::layoutBlockClasses(), ...$this->blocks]
            : $this->blocks;

        $registry = $this->getRegistry()->register($blocks);

        $registry->allowEmbedHosts($this->embedHosts);

        $shortcodes = $registry->shortcodes();

        if ($this->includeLayoutBlocks) {
            $shortcodes
                ->register('year', fn (): string => date('Y'), 'The current year.', '[year]')
                ->register(
                    'date',
                    fn (array $attributes): string => e(now()->format(is_string($attributes['format'] ?? null) ? $attributes['format'] : 'F j, Y')),
                    "Today's date. Optional format, as PHP writes dates.",
                    '[date format="F j, Y"]',
                );
        }

        foreach ($this->shortcodes as $name => $shortcode) {
            $shortcodes->register($name, $shortcode['callback'], $shortcode['description'], $shortcode['example']);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
