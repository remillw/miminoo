export interface Column<T = any> {
    key: string;
    label: string;
    sortable?: boolean;
    searchable?: boolean;
    width?: string;
    render?: (value: any, item: T) => string | number;
    slot?: string; // Pour les slots personnalisés
}

export interface DataTableProps<T> {
    data: T[];
    columns: Column<T>[];
    searchPlaceholder?: string;
    emptyMessage?: string;
    loading?: boolean;
}
