// Type declarations for packages without TypeScript support
declare module 'phpunserialize' {
  export function unserialize(data: string): any;
}

declare module 'vue-json-pretty' {
  import { DefineComponent } from 'vue';
  const VueJsonPretty: DefineComponent<{}, {}, any>;
  export default VueJsonPretty;
}

declare module 'sql-formatter' {
  export function format(query: string, options?: any): string;
}

// Global type declarations
interface Window {
  Horizon: {
    basePath: string;
    path: string;
    proxy_path: string;
  };
}