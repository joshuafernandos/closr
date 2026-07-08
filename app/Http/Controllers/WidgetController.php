<?php

namespace App\Http\Controllers;

use App\Http\Requests\Widgets\StoreWidgetRequest;
use App\Http\Requests\Widgets\UpdateWidgetRequest;
use App\Models\Business;
use App\Models\CatalogueOrigin;
use App\Models\Widget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WidgetController extends Controller
{
    /**
     * List the current business's widgets, with its sources for the create form.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Widget::class);

        $business = $request->user()->currentBusiness;

        return Inertia::render('widgets', [
            'widgets' => $business->widgets()
                ->with('catalogueOrigin')
                ->latest()
                ->get()
                ->map(fn (Widget $widget): array => $this->present($widget))
                ->all(),
            'sources' => $this->sources($business),
        ]);
    }

    /**
     * Create a new widget for the current business.
     */
    public function store(StoreWidgetRequest $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness;

        $widget = $business->widgets()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Widget created.')]);

        return to_route('widgets.edit', $widget);
    }

    /**
     * Show the configuration page for a widget.
     */
    public function edit(Request $request, Widget $widget): Response
    {
        Gate::authorize('update', $widget);

        $business = $request->user()->currentBusiness;

        return Inertia::render('widgets/edit', [
            'widget' => $this->present($widget),
            'sources' => $this->sources($business),
            'widgetUrl' => route('widget', $widget),
            'embedUrl' => route('widget.embed', $widget),
        ]);
    }

    /**
     * Update a widget's configuration.
     */
    public function update(UpdateWidgetRequest $request, Widget $widget): RedirectResponse
    {
        $widget->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Widget updated.')]);

        return to_route('widgets.edit', $widget);
    }

    /**
     * Delete a widget.
     */
    public function destroy(Widget $widget): RedirectResponse
    {
        Gate::authorize('delete', $widget);

        $widget->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Widget deleted.')]);

        return to_route('widgets.index');
    }

    /**
     * Present a widget for the frontend.
     *
     * @return array<string, mixed>
     */
    private function present(Widget $widget): array
    {
        return [
            'id' => $widget->id,
            'key' => $widget->widget_key,
            'name' => $widget->name,
            'template' => $widget->template->value,
            'accentColor' => $widget->accent_color,
            'catalogueOriginId' => $widget->catalogue_origin_id,
            'sourceName' => $widget->catalogueOrigin?->name,
        ];
    }

    /**
     * The business's catalogue sources, for the widget's source picker.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sources(Business $business): array
    {
        return $business->catalogueOrigins()
            ->latest()
            ->get()
            ->map(fn (CatalogueOrigin $origin): array => [
                'id' => $origin->id,
                'name' => $origin->name ?? (string) ($origin->config['url'] ?? ''),
                'driver' => $origin->driver,
            ])
            ->all();
    }
}
