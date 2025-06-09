declare global {
    interface Window {
        google?: typeof google;
    }
}

declare namespace google {
    namespace maps {
        class LatLng {
            constructor(lat: number, lng: number);
            lat(): number;
            lng(): number;
        }

        namespace places {
            class Autocomplete {
                constructor(input: HTMLInputElement, options?: AutocompleteOptions);
                addListener(eventName: string, handler: Function): void;
                getPlace(): PlaceResult;
            }

            interface AutocompleteOptions {
                types?: string[];
                componentRestrictions?: ComponentRestrictions;
            }

            interface ComponentRestrictions {
                country?: string | string[];
            }

            interface PlaceResult {
                formatted_address?: string;
                place_id?: string;
                geometry?: {
                    location?: LatLng;
                };
                address_components?: AddressComponent[];
            }

            interface AddressComponent {
                long_name: string;
                short_name: string;
                types: string[];
            }
        }
    }
}

export {};
