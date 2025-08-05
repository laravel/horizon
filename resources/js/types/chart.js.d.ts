declare module 'chart.js' {
    export default class Chart {
        constructor(ctx: any, config: any);
        destroy(): void;
        update(): void;
        chart?: any;
        data?: any;
    }
    
    export interface ChartData {
        labels?: any[];
        datasets?: any[];
    }
}