<x-filament-panels::page>
    <div
        class="fpb"
        x-data="pageBuilderCanvas()"
        :data-workspace="workspace"
        :data-narrow="narrow ? 'true' : 'false'"
        :data-wide="wideInspector ? 'true' : 'false'"
        wire:key="fpb-{{ $this->getRecord()->getKey() }}"
    >
        <header class="fpb-chrome">
            <div class="fpb-chrome-start">
                @if ($exitUrl = $this->exitUrl())
                    <a href="{{ $exitUrl }}" class="fpb-back">{{ $this->exitLabel() }}</a>
                @endif

                <h1 class="fpb-chrome-title">{{ $this->getRecordTitle() }}</h1>

                @if ($this->selectionPath !== [])
                    <nav class="fpb-crumbs" aria-label="Selected block">
                        @foreach ($this->selectionPath as $crumb)
                            <button
                                type="button"
                                class="fpb-crumb"
                                @if ($crumb['id'] === $this->selectedId) data-current="true" @endif
                                wire:click="selectBlock('{{ $crumb['id'] }}')"
                            >{{ $crumb['label'] }}</button>
                            @unless ($loop->last)
                                <span class="fpb-crumb-sep" aria-hidden="true">/</span>
                            @endunless
                        @endforeach
                        <span class="fpb-crumb-field" x-show="editingField" x-cloak>
                            <span class="fpb-crumb-sep" aria-hidden="true">/</span>
                            <span x-text="editingField"></span>
                        </span>
                    </nav>
                @endif

                <span class="fpb-status" @class(['fpb-status-dirty' => $this->isDirty])>
                    {{ $this->isDirty ? 'Draft not saved' : 'Saved' }}
                </span>
                <span class="fpb-notice" x-show="notice" x-text="notice" x-cloak role="status"></span>
            </div>

            <div class="fpb-preview-toggle fpb-desktop-only" role="group" aria-label="Canvas width">
                <button type="button" class="fpb-preview-btn" :aria-pressed="preview === 'desktop'" :data-active="preview === 'desktop'" x-on:click="preview = 'desktop'">Desktop</button>
                <button type="button" class="fpb-preview-btn" :aria-pressed="preview === 'tablet'" :data-active="preview === 'tablet'" x-on:click="preview = 'tablet'">Tablet <span class="fpb-preview-px">768</span></button>
                <button type="button" class="fpb-preview-btn" :aria-pressed="preview === 'mobile'" :data-active="preview === 'mobile'" x-on:click="preview = 'mobile'">Mobile <span class="fpb-preview-px">390</span></button>
            </div>

            <div class="fpb-toolbar-actions">
                <x-filament::icon-button
                    icon="heroicon-m-arrow-uturn-left"
                    label="Undo"
                    color="gray"
                    size="sm"
                    wire:click="undo"
                    :disabled="! $this->canUndo"
                />

                <x-filament::icon-button
                    icon="heroicon-m-arrow-uturn-right"
                    label="Redo"
                    color="gray"
                    size="sm"
                    wire:click="redo"
                    :disabled="! $this->canRedo"
                />

                @if ($formEditorUrl = $this->formEditorUrl())
                    <span class="fpb-desktop-only">
                        @if ($this->hasNestedBlocks())
                            <x-filament::button
                                tag="a"
                                href="{{ $formEditorUrl }}"
                                color="gray"
                                size="sm"
                                x-on:click="if (! confirm('This page uses columns. The form view cannot show that layout correctly and may delete or duplicate content. Open anyway?')) { $event.preventDefault() }"
                            >
                                Form editor
                            </x-filament::button>
                        @else
                            <x-filament::button
                                tag="a"
                                href="{{ $formEditorUrl }}"
                                color="gray"
                                size="sm"
                            >
                                Form editor
                            </x-filament::button>
                        @endif
                    </span>
                @endif

                <x-filament::button
                    color="gray"
                    size="sm"
                    icon="heroicon-m-eye"
                    x-on:click="openPreview()"
                    title="See the page as visitors will, unsaved changes included"
                >
                    Preview
                </x-filament::button>

                <x-filament::button
                    wire:click="save"
                    wire:loading.attr="disabled"
                    size="sm"
                >
                    Save layout
                </x-filament::button>
            </div>
        </header>

        <aside class="fpb-panel fpb-palette" x-show="!narrow || workspace === 'blocks'">
            <div class="fpb-side-tabs" role="tablist" aria-label="Blocks and outline">
                <button
                    type="button"
                    role="tab"
                    id="fpb-tab-blocks"
                    class="fpb-side-tab"
                    aria-controls="fpb-panel-blocks"
                    :aria-selected="sideTab === 'blocks'"
                    :data-active="sideTab === 'blocks'"
                    x-on:click="sideTab = 'blocks'"
                >Blocks</button>
                <button
                    type="button"
                    role="tab"
                    id="fpb-tab-structure"
                    class="fpb-side-tab"
                    aria-controls="fpb-panel-structure"
                    :aria-selected="sideTab === 'structure'"
                    :data-active="sideTab === 'structure'"
                    x-on:click="sideTab = 'structure'"
                >Structure</button>
            </div>

            <div id="fpb-panel-blocks" role="tabpanel" aria-labelledby="fpb-tab-blocks" x-show="sideTab === 'blocks'">
                <p class="fpb-panel-hint" x-text="$wire.selectedId
                    ? 'Click to add after the selection, or drag onto a column.'
                    : 'Click to add at the end of the page, or drag onto the canvas.'">
                    Drag onto the page or into a column. Click to insert at the selection.
                </p>

                <label class="fpb-search">
                    <span class="sr-only">Search blocks</span>
                    <input
                        type="search"
                        class="fpb-search-input"
                        placeholder="Search blocks"
                        x-ref="paletteSearch"
                        x-model="paletteQuery"
                    >
                </label>

                @foreach ($this->paletteGroups as $group)
                    <div class="fpb-palette-group-wrap" x-show="[...$el.querySelectorAll('[data-fpb-search]')].some((item) => matchesPalette(item.dataset.fpbSearch))">
                        <h3 class="fpb-palette-group">{{ $group['label'] }}</h3>
                        <ul class="fpb-palette-list">
                            @foreach ($group['items'] as $item)
                                <li
                                    data-fpb-search="{{ strtolower($item['label'].' '.$item['type'].' '.($item['description'] ?? '')) }}"
                                    x-show="matchesPalette($el.dataset.fpbSearch)"
                                >
                                    <button
                                        type="button"
                                        class="fpb-palette-item"
                                        draggable="true"
                                        data-type="{{ $item['type'] }}"
                                        :draggable="!narrow"
                                        x-on:dragstart="startInsert($event, '{{ $item['type'] }}')"
                                        x-on:dragend="clearDrag()"
                                        x-on:click="insertFromPalette('{{ $item['type'] }}')"
                                    >
                                        @if ($item['icon'])
                                            <x-filament::icon :icon="$item['icon']" class="fpb-palette-icon" />
                                        @endif
                                        <span class="fpb-palette-copy">
                                            <span>{{ $item['label'] }}</span>
                                            @if ($item['description'])
                                                <span class="fpb-palette-desc">{{ $item['description'] }}</span>
                                            @endif
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <div id="fpb-panel-structure" role="tabpanel" aria-labelledby="fpb-tab-structure" x-show="sideTab === 'structure'" x-cloak>
                <h2 class="fpb-panel-title">Document</h2>
                <p class="fpb-panel-hint">Click a block to select it. The outline follows the page.</p>

                @if ($this->structure === [])
                    <p class="fpb-panel-hint">This page has no blocks yet.</p>
                @else
                    <ol class="fpb-structure">
                        @foreach ($this->structure as $node)
                            @include('page-builder::structure-node', ['node' => $node, 'depth' => 0])
                        @endforeach
                    </ol>
                @endif

                @if ($this->ghosts !== [])
                    <div class="fpb-ghosts">
                        <h3 class="fpb-palette-group">Hidden ({{ count($this->ghosts) }})</h3>
                        <p class="fpb-panel-hint">Stored on the page but not shown — an orphaned parent or a removed column.</p>
                        <ul class="fpb-ghost-list">
                            @foreach ($this->ghosts as $ghost)
                                <li>
                                    <span>{{ $ghost['label'] }}</span>
                                    <button type="button" class="fpb-ghost-reveal" wire:click="revealGhost('{{ $ghost['id'] }}')" x-on:click="afterRevealOnCanvas()">
                                        {{ $ghost['reason'] === 'orphan' ? 'Move to page' : 'Move to last column' }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <dl class="fpb-shortcuts fpb-desktop-only">
                <dt x-text="isMac ? '⌘S' : 'Ctrl+S'">Ctrl+S</dt><dd>Save</dd>
                <dt x-text="isMac ? '⌘Z' : 'Ctrl+Z'">Ctrl+Z</dt><dd>Undo</dd>
                <dt x-text="isMac ? '⇧⌘Z' : 'Ctrl+Shift+Z'">Ctrl+Shift+Z</dt><dd>Redo</dd>
                <dt x-text="isMac ? '⌘C ⌘V' : 'Ctrl+C Ctrl+V'">Ctrl+C Ctrl+V</dt><dd>Copy, paste</dd>
                <dt x-text="isMac ? '⌘D' : 'Ctrl+D'">Ctrl+D</dt><dd>Duplicate</dd>
                <dt>⇧↑ ⇧↓</dt><dd>Move block</dd>
                <dt>↑ ↓</dt><dd>Select</dd>
                <dt>⌫</dt><dd>Delete</dd>
                <dt>Esc</dt><dd>Deselect</dd>
                <dt>Right-click</dt><dd>More actions</dd>
            </dl>
        </aside>

        <main class="fpb-canvas-wrap" x-show="!narrow || workspace === 'canvas'">
            <div class="fpb-canvas-frame" x-ref="frame">
                <div
                    class="fpb-canvas"
                    :data-preview="preview"
                    x-on:dragover.prevent="onDragOver($event)"
                    x-on:drop.prevent="onDrop($event)"
                    x-on:dragleave="onDragLeave($event)"
                    x-on:contextmenu="onContextMenu($event)"
                >
                    @if ($stylesView = $this->canvasStylesView())
                        @include($stylesView)
                    @endif
                    @forelse ($this->rootBlocks as $block)
                        <x-page-builder::canvas-block :block="$block" :selected-id="$this->selectedId" />
                    @empty
                        <div class="fpb-empty">
                            <p class="fpb-empty-title">Start a layout</p>
                            <p class="fpb-empty-copy">
                                <span class="fpb-empty-copy-wide">Add a row of columns, or drop a block from the left.</span>
                                <span class="fpb-empty-copy-narrow">Add a row of columns, or tap Blocks to pick one.</span>
                            </p>
                            <div class="fpb-empty-actions">
                                @foreach (array_slice($this->palette, 0, 3) as $item)
                                    <button
                                        type="button"
                                        class="fpb-empty-btn"
                                        x-on:click="insertFromPalette('{{ $item['type'] }}')"
                                    >Add {{ $item['label'] }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Editor decoration that must never be part of the page: the drop
                     indicator, column handles and size readouts. It sits outside the
                     canvas so drawing it cannot move a block or wake the canvas's
                     mutation observer, and Livewire leaves it alone. --}}
                <div class="fpb-overlay" x-ref="overlay" wire:ignore aria-hidden="true">
                    <div class="fpb-drop-indicator" x-ref="indicator">
                        <span class="fpb-drop-cap"></span>
                        <span class="fpb-drop-label" x-ref="indicatorLabel"></span>
                        <span class="fpb-drop-cap"></span>
                    </div>
                    <div class="fpb-size-tip" x-ref="sizeTip"></div>
                </div>
            </div>
        </main>

        <aside class="fpb-panel fpb-inspector" x-show="!narrow || workspace === 'settings'">
            @if ($selected = $this->selectedBlock)
                <header class="fpb-inspector-head">
                    <span class="fpb-inspector-icon" aria-hidden="true">
                        @if ($selected['icon'])
                            <x-filament::icon :icon="$selected['icon']" />
                        @endif
                    </span>
                    <div class="fpb-inspector-heading">
                        <h2 class="fpb-panel-title">{{ $selected['label'] }}</h2>
                        @if ($selected['parentId'])
                            <button type="button" class="fpb-inspector-parent" wire:click="selectBlock('{{ $selected['parentId'] }}')">
                                ↑ Inside {{ $selected['parentLabel'] }}
                            </button>
                        @elseif ($selected['description'])
                            <p class="fpb-inspector-desc">{{ $selected['description'] }}</p>
                        @endif
                    </div>
                    <div class="fpb-inspector-actions">
                        <button
                            type="button"
                            class="fpb-icon-btn fpb-desktop-only"
                            x-on:click="wideInspector = ! wideInspector"
                            :aria-pressed="wideInspector ? 'true' : 'false'"
                            :title="wideInspector ? 'Make the panel narrower' : 'Make the panel wider'"
                        >
                            <x-filament::icon icon="heroicon-m-arrows-right-left" class="fpb-icon" />
                        </button>
                        <button type="button" class="fpb-icon-btn" wire:click="selectBlock(null)" title="Close (Esc)">
                            <x-filament::icon icon="heroicon-m-x-mark" class="fpb-icon" />
                        </button>
                    </div>
                </header>

                <div class="fpb-inspector-tabs" role="tablist" aria-label="Block inspector">
                    <button type="button" role="tab" id="fpb-tab-content" class="fpb-side-tab" aria-controls="fpb-panel-content" :aria-selected="inspectorTab === 'content'" :data-active="inspectorTab === 'content'" x-on:click="inspectorTab = 'content'">Content</button>
                    <button type="button" role="tab" id="fpb-tab-style" class="fpb-side-tab" aria-controls="fpb-panel-style" :aria-selected="inspectorTab === 'style'" :data-active="inspectorTab === 'style'" x-on:click="inspectorTab = 'style'">Style</button>
                    <button type="button" role="tab" id="fpb-tab-layout" class="fpb-side-tab" aria-controls="fpb-panel-layout" :aria-selected="inspectorTab === 'layout'" :data-active="inspectorTab === 'layout'" x-on:click="inspectorTab = 'layout'">Layout</button>
                </div>

                <div id="fpb-panel-content" role="tabpanel" aria-labelledby="fpb-tab-content" x-show="inspectorTab === 'content'">
                    @if ($this->isSelectedBlockEditable())
                        {{ $this->form }}
                    @elseif (! $this->isSelectedBlockKnown())
                        <p class="fpb-panel-hint">
                            This block's type is no longer registered, so there are no fields to show.
                            Its stored content is preserved. You can still move or remove it.
                        </p>
                    @else
                        <p class="fpb-panel-hint">
                            You do not have permission to edit this block's content. You can still
                            move or remove it.
                        </p>
                    @endif
                </div>

                <div id="fpb-panel-style" role="tabpanel" aria-labelledby="fpb-tab-style" class="fpb-style-panel" x-show="inspectorTab === 'style'" x-cloak>
                    @if ($this->isSelectedBlockEditable() && $this->hasCustomStyles())
                        {{ $this->styleForm }}
                    @elseif (! $this->isSelectedBlockEditable())
                        <p class="fpb-panel-hint">Only people who may edit this block can restyle it.</p>
                    @endif

                    @if ($this->styleTokens() !== [])
                        <details class="fpb-presets" @if (! $this->hasCustomStyles()) open @endif>
                            <summary>Brand presets</summary>
                            <p class="fpb-panel-hint">Spacing and colours from your organisation's style guide.</p>

                            @foreach ($this->styleTokens() as $token => $options)
                                <fieldset class="fpb-style-field">
                                    <legend>{{ ucfirst($token) }}</legend>
                                    <div class="fpb-token-picks" role="radiogroup" aria-label="{{ ucfirst($token) }}">
                                        <button
                                            type="button"
                                            class="fpb-token-pick"
                                            @if (($this->blockSettings[$token] ?? '') === '') data-active="true" @endif
                                            wire:click="$set('blockSettings.{{ $token }}', '')"
                                        >Default</button>
                                        @foreach ($options as $value => $label)
                                            @php
                                                $stored = is_int($value) ? $label : $value;
                                            @endphp
                                            <button
                                                type="button"
                                                class="fpb-token-pick"
                                                @if (($this->blockSettings[$token] ?? '') === $stored) data-active="true" @endif
                                                wire:click="$set('blockSettings.{{ $token }}', '{{ $stored }}')"
                                            >{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach
                        </details>
                    @endif
                </div>

                <div id="fpb-panel-layout" role="tabpanel" aria-labelledby="fpb-tab-layout" class="fpb-style-panel" x-show="inspectorTab === 'layout'" x-cloak>
                    @if ($this->isSelectedBlockEditable() && $this->hasCustomStyles())
                        {{ $this->layoutForm }}
                    @endif

                    <label class="fpb-style-field fpb-anchor-field">
                        <span>Anchor</span>
                        <input
                            type="text"
                            class="fpb-search-input"
                            wire:model.blur="blockAnchor"
                            placeholder="intro"
                            autocomplete="off"
                        >
                        <small class="fpb-panel-hint">Link to this block with <code>#intro</code>.</small>
                    </label>
                </div>
            @else
                <h2 class="fpb-panel-title">Nothing selected</h2>
                <p class="fpb-panel-hint">
                    <span class="fpb-empty-copy-wide">Click a block on the page to edit its content, style and layout. Right-click for more.</span>
                    <span class="fpb-empty-copy-narrow">Select a block on the Page tab, then come back here to edit it.</span>
                </p>
            @endif
        </aside>

        <button
            type="button"
            class="fpb-add"
            x-show="narrow && workspace === 'canvas'"
            x-cloak
            x-on:click="showWorkspace('blocks')"
            aria-label="Add a block"
        >+</button>

        <nav class="fpb-dock" x-show="narrow" x-cloak role="tablist" aria-label="Design workspace">
            <button
                type="button"
                role="tab"
                class="fpb-dock-btn"
                :aria-selected="workspace === 'blocks'"
                :data-active="workspace === 'blocks'"
                x-on:click="showWorkspace('blocks')"
            >Blocks</button>
            <button
                type="button"
                role="tab"
                class="fpb-dock-btn"
                :aria-selected="workspace === 'canvas'"
                :data-active="workspace === 'canvas'"
                x-on:click="showWorkspace('canvas')"
            >Page</button>
            <button
                type="button"
                role="tab"
                class="fpb-dock-btn"
                :aria-selected="workspace === 'settings'"
                :data-active="workspace === 'settings'"
                x-on:click="showWorkspace('settings')"
            >
                Settings
                <span class="fpb-dock-dot" x-show="$wire.selectedId" x-cloak aria-hidden="true"></span>
            </button>
        </nav>

        {{-- Right-click menu. Alpine owns it; Livewire must not redraw it mid-click. --}}
        <div
            class="fpb-menu"
            x-ref="menu"
            x-show="menu.open"
            x-cloak
            wire:ignore
            role="menu"
            aria-label="Block actions"
            :style="`left: ${menu.x}px; top: ${menu.y}px`"
            x-on:click.outside="closeMenu()"
            x-on:contextmenu.prevent
        >
            <p class="fpb-menu-title" x-text="menu.label"></p>
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuInspect('content')"><span>Edit content</span></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuInspect('style')"><span>Style</span></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuInspect('layout')"><span>Spacing and size</span></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-show="menu.parent" x-on:click="menuSelectParent()"><span>Select parent</span></button>
            <hr class="fpb-menu-sep">
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuCopy()"><span>Copy</span><kbd x-text="keys('C')"></kbd></button>
            <button type="button" role="menuitem" class="fpb-menu-item" :disabled="! clipboard" x-on:click="menuPaste('after')"><span>Paste after</span><kbd x-text="keys('V')"></kbd></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-show="menu.container" :disabled="! clipboard" x-on:click="menuPaste('inside')"><span>Paste inside</span></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuCopyStyle()"><span>Copy style</span></button>
            <button type="button" role="menuitem" class="fpb-menu-item" :disabled="! styleClipboard" x-on:click="menuPasteStyle()"><span>Paste style</span></button>
            <hr class="fpb-menu-sep">
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuAddBelow()"><span>Add a block below…</span></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuDuplicate()"><span>Duplicate</span><kbd x-text="keys('D')"></kbd></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuMove(-1)"><span>Move up</span><kbd>⇧↑</kbd></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuMove(1)"><span>Move down</span><kbd>⇧↓</kbd></button>
            <button type="button" role="menuitem" class="fpb-menu-item" x-on:click="menuResetStyle()"><span>Reset style</span></button>
            <hr class="fpb-menu-sep">
            <button type="button" role="menuitem" class="fpb-menu-item fpb-menu-danger" x-on:click="menuDelete()"><span>Delete</span><kbd>⌫</kbd></button>
        </div>

        {{-- Preview: the unsaved page, rendered for visitors, at a real width. --}}
        <div class="fpb-preview" x-show="previewing" x-cloak wire:ignore role="dialog" aria-modal="true" aria-label="Page preview">
            <header class="fpb-preview-bar">
                <div class="fpb-preview-heading">
                    <strong>Preview</strong>
                    <span class="fpb-preview-note">Unsaved changes included. Links, video and custom code are live.</span>
                </div>
                <div class="fpb-preview-toggle" role="group" aria-label="Preview width">
                    <button type="button" class="fpb-preview-btn" :data-active="previewDevice === 'desktop'" x-on:click="previewDevice = 'desktop'">Desktop</button>
                    <button type="button" class="fpb-preview-btn" :data-active="previewDevice === 'tablet'" x-on:click="previewDevice = 'tablet'">Tablet <span class="fpb-preview-px">768</span></button>
                    <button type="button" class="fpb-preview-btn" :data-active="previewDevice === 'mobile'" x-on:click="previewDevice = 'mobile'">Mobile <span class="fpb-preview-px">390</span></button>
                </div>
                <div class="fpb-preview-actions">
                    <button type="button" class="fpb-preview-btn" x-on:click="refreshPreview()">Refresh</button>
                    <button type="button" class="fpb-preview-close" x-on:click="closePreview()">Back to editing <kbd>Esc</kbd></button>
                </div>
            </header>
            <div class="fpb-preview-stage">
                <iframe
                    class="fpb-preview-frame"
                    x-ref="previewFrame"
                    :data-device="previewDevice"
                    title="Page preview"
                    sandbox="allow-scripts allow-same-origin allow-popups allow-forms allow-presentation allow-modals"
                ></iframe>
                <p class="fpb-preview-loading" x-show="previewLoading">Rendering the page…</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
