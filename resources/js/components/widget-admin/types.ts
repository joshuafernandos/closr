export type WidgetTemplate = 'chat' | 'component';

export interface WidgetSource {
    id: number;
    name: string;
    driver: string;
}

export interface WidgetSummary {
    id: number;
    key: string;
    name: string;
    template: WidgetTemplate;
    accentColor: string;
    catalogueOriginId: number | null;
    sourceName: string | null;
}
