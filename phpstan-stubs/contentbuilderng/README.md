# PHPStan stubs — com_contentbuilderng

`com_contentbuilderng` is an optional, separately-installed sibling component
(see `RenderingEngine::view()`'s `ContentBuilderInstallation::isInstalled()`
guards). Its source isn't part of this repository or CI's checkout, so
PHPStan has nothing to resolve the handful of `CB\Component\Contentbuilderng\*`
symbols this component calls against, once installed at runtime.

These files are **signature-only stubs**, not real implementations: class
and method shapes copied from the real com_contentbuilderng source, with
trivial bodies, so PHPStan can type-check the call sites without either
(a) depending on a sibling checkout that doesn't exist in CI, or (b) leaving
every ContentBuilder integration point permanently baselined.

They're wired in via `scanDirectories` in `phpstan.neon.dist`, not `paths` —
PHPStan reflects their declarations but never lints their (irrelevant) bodies
and they're never included in the shipped package.

**Keep these in sync manually** if com_contentbuilderng's public API for the
methods listed below changes; there's no automated check tying them together.

Symbols stubbed, and the file in this component that calls each one:

| Symbol | Caller |
|---|---|
| `Helper\FormSourceFactory::getForm()` | `RenderingEngine`, `ContentBuilderFormSync` |
| `Helper\ContentbuilderngHelper::contentbuilderng_wordwrap()` | `RenderingEngine` |
| `Service\FormSupportService::synchElements()` (from the ContentBuilder NG container) | `ContentBuilderFormSync` |
| `Service\ListSupportService::createFromRuntimeContext()` | `RenderingEngine`, `ExportEngine` |
| `Service\PermissionService::createFromRuntimeContext()` | `RenderingEngine` |
| `Service\ArticleService` (class only, via `::class`) | `ExportEngine` |
| `Extension\ContentbuilderngComponent::getContainer()` | `ExportEngine`, `ContentBuilderFormSync` |
