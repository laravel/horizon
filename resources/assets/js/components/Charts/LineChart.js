import Line from './BaseCharts/Line';
import reactiveProp from './mixins/reactiveProp';

export default Line.extend({
    props: ['options'],


    mixins: [reactiveProp],


    mounted() {
        this.renderChart(this.chartData, this.options);
    }
});
