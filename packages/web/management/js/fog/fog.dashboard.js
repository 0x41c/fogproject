var JSONParseFunction = (typeof(JSON) != 'undefined' ? JSON.parse : eval)
var bandwidthtime = $('#bandwidthtime').val();
// Disk Usage Graph Stuff
var GraphDiskUsage = $('#graph-diskusage','#content-inner');
var GraphDiskUsageAJAX;
var GraphDiskUsageNode = $('#diskusage-selector select','#content-inner');
var ClientCountGroup = $('#graph-activity-selector select','#content-inner');
var NodeID;
var GroupID;
var GraphDiskUsageData = [{label: 'Free',data:0},{label: 'Used',data:0}];
var bytes, units;
var GraphDiskUsageOpts = {
    colors: ['#45a73c','#cb4b4b'],
    series: {
        pie: {
            show: true,
            radius: 1
        }
    },
    legend: {
        show: true,
        align: 'right',
        position: 'se',
        labelColor: '#666',
        labelFormatter: function(label, series) {
            units = [' iB',' KiB',' MiB',' GiB',' TiB',' PiB',' EiB',' ZiB',' YiB'];
            for (i =0; series.data[0][1] >= 1024 && i < units.length -1; i++) series.data[0][1] /= 1024;
            return '<div style="font-size:8pt;padding:2px;">'+label+': '+Math.round(series.percent)+'% <br />'+series.data[0][1].toFixed(2)+units[i]+'</div>';
        }
    }
};
// Bandwidth Variable/Option settings.
var GraphData = new Array();
var GraphBandwidth = $('#graph-bandwidth','#content-inner');
var GraphBandwidthFilterTransmit = $('#graph-bandwidth-filters-transmit','#graph-bandwidth-filters');
var GraphBandwidthFilterTransmitActive = GraphBandwidthFilterTransmit.hasClass('active');
var GraphBandwidthData = new Array();
var GraphBandwidthdata = [];
var GraphBandwidthMaxDataPoints;
var UpdateTimeout;
var GraphBandwidthOpts = {
    xaxis: {mode: 'time'},
    yaxis: {
        min: 0,
        tickFormatter: function(v) {return v+' Mbps';}
    },
    series: {
        lines: {show: true},
        shadowSize: 0
    },
    legend: {
        show: true,
        position: 'nw',
        noColumns: 5,
        labelFormatter: function(label, series) {return label;}
    }
};
var GraphBandwidthFilters = $('#graph-bandwidth-filters-transmit, #graph-bandwidth-filters-receive', '#graph-bandwidth-filters');
var GraphBandwidthAJAX;
// 30 Day Data
var Graph30Day = $('#graph-30day', '#content-inner');
var Graph30DayData;
var Graph30DayOpts = {
    colors: ['#7386ad'],
    xaxis: {mode: 'time'},
    yaxis: {
        tickFormatter: function(v) {return '<div style="width: 35px; text-align: right; padding-right: 7px;">'+v+'</div>';},
        min: 0,
        minTickSize: 1
    },
    series: {
        lines: {
            show: true,
            fill: true
        },
        points: {show: true}
    },
    legend: {position: 'nw'}
};
// Client Count variables
var GraphClient = $('#graph-activity','#content-inner');
var UpdateClientCountData = [[0,0]];
var UpdateClientCountOpts = {
    colors: ['#cb4b4b','#7386ad','#45a73c'],
    series: {
        pie: {
            show: true,
            radius: 1,
        }
    },
    legend: {
        show: true,
        align: 'right',
        position: 'se',
        labelColor: '#666',
        labelFormatter: function(label, series) {
            return '<div style="font-size:8pt;padding:2px;">'+label+': '+series.datapoints.points[1]+'</div>';
        }
    }
};
$(function() {
    // Diskusage Graph - Node select - Hook select box to load new data via AJAX
    GraphDiskUsageUpdate();
    UpdateClientCount();
    $('#diskusage-selector select').change(function(e) {
        GraphDiskUsageUpdate();
        e.preventDefault();
    });
    $('#graph-activity-selector select').change(function(e) {
        UpdateClientCount();
        e.preventDefault();
    });
    // Client Count starter.
    // Only start bandwidth once the page is fully loaded.
    // 30 Day History Graph
    if (typeof(Graph30dayData) != 'undefined') Graph30DayData = [{label: 'Computers Imaged',data: JSONParseFunction(Graph30dayData)}];
    $.plot(Graph30Day,Graph30DayData,Graph30DayOpts);
    // Start counters
    setInterval(UpdateBandwidth,bandwidthtime);
    // Bandwidth Graph - TX/RX Filter
    GraphBandwidthFilters.click(function(e) {
        // Blur -> add active class -> remove active class from old active item
        $(this).blur().addClass('active').siblings('a').removeClass('active');
        // Update title
        $('#graph-bandwidth-title > span').eq(0).html($(this).html());
        GraphBandwidthFilterTransmitActive = (GraphBandwidthFilterTransmit.hasClass('active') ? true : false);
        // On click change
        // Prevent default action
        e.preventDefault();
    });
    GraphBandwidthMaxDataPoints = $('#graph-bandwidth-filters div:eq(2) a.active').prop('rel');
    // Bandwidth Graph - Time Filter
    $('#graph-bandwidth-filters div:eq(2) a').click(function(e) {
        // Blur -> add active class -> remove active class from old active item
        $(this).blur().addClass('active').siblings('a').removeClass('active');
        // Update title
        $('#graph-bandwidth-title > span').eq(1).html($(this).html());
        // Update max data points variable
        GraphBandwidthMaxDataPoints = this.rel;
        // Prevent default action
        e.preventDefault();
    });
    // Remove loading spinners
    $('.graph').not(GraphBandwidth,GraphDiskUsage).addClass('loaded');
});
// Disk Usage Functions
function GraphDiskUsageUpdate() {
    if (GraphDiskUsageAJAX) GraphDiskUsageAJAX.abort();
    NodeID = GraphDiskUsageNode.val();
    if (NodeID === null || typeof(NodeID) == 'undefined' || NodeID.length === 0) return;
    GraphDiskUsageAJAX = $.ajax({
        url: '?node=home',
        type: 'POST',
        data: {
            sub: 'diskusage',
            id:NodeID
        },
        dataType: 'json',
        beforeSend: function() {GraphDiskUsage.html('').removeClass('loaded').parents('a').prop('href','?node=hwinfo&id='+NodeID);},
        success: GraphDiskUsagePlots,
        complete: function() {setTimeout(GraphDiskUsageUpdate,120000);}
    });
}
function GraphDiskUsagePlots(data) {
    if (data === null || typeof(data) == 'undefined' || data.length === 0) return;
    if (typeof(data.error) != 'undefined') {
        GraphDiskUsage.html((data.error ? data.error : 'No error, but no data was returned')).addClass('loaded');
        return;
    };
    GraphDiskUsageData = [{label: 'Free',data: parseInt(data.free,10)},{label: 'Used',data: parseInt(data.used,10)}];
    $.plot(GraphDiskUsage,GraphDiskUsageData,GraphDiskUsageOpts);
    GraphDiskUsage.addClass('loaded');
}
// Bandwidth Functions
function UpdateBandwidth() {
    $.ajax({
        url: '?node=home',
        type: 'POST',
        data: {
            sub: 'bandwidth'
        },
        dataType: 'json',
        success: function(data) {
            setTimeout(UpdateBandwidthGraph,500);
        },
        error: function(jqXHR, textStatus) {
            console.log(textStatus);
        },
        complete: function() {GraphBandwidth.addClass('loaded');}
    });
}
function UpdateBandwidthGraph(data) {
    if (data === null || typeof(data) == 'undefined' || data.length == 0) return;
    //if (!GraphBandwidthOpts.colors) {
    //    GraphBandwidthOpts.colors = $.map(data,function(o,i) {
    //        console.log(i);
    //        return '#'+('00000'+(Math.random()*(1<<24)|0).toString(16)).slice(-6);
    //    });
    //}
    var d = new Date();
    var tx = new Array();
    var rx = new Array();
    var tx_old = new Array();
    var rx_old = new Array();
    Now = new Date().getTime() - (d.getTimezoneOffset() * 60000);
    var nodes_count = data.length;
    for (i in data) {
        // Setup all the values we may need.
        if (typeof(GraphBandwidthData[i]) == 'undefined') {
            GraphBandwidthData[i] = new Array();
            GraphBandwidthData[i].dev = new Array();
            GraphBandwidthData[i].tx = new Array();
            GraphBandwidthData[i].rx = new Array();
        }
        while (GraphBandwidthData[i].tx.length >= GraphBandwidthMaxDataPoints) {
            GraphBandwidthData[i].tx.shift();
            GraphBandwidthData[i].rx.shift();
        }
        if (data[i] === null) data[i] = {dev: 'Unknown',tx:0,rx:0};
        data[i].tx = (data[i].tx > 0 ? data[i].tx / bandwidthtime : 0);
        data[i].rx = (data[i].rx > 0 ? data[i].rx / bandwidthtime : 0);
        // Set the old values and wait one second.
        if (GraphBandwidthData[i].tx_old > 0 && data[i].tx > 0) {
            tx_rate = Math.round(((data[i].tx - GraphBandwidthData[i].tx_old)));
            GraphBandwidthData[i].tx.push([Now,tx_rate]);
        } else GraphBandwidthData[i].tx.push([Now,0]);
        if (GraphBandwidthData[i].rx_old > 0 && data[i].rx > 0) {
            rx_rate = Math.round(((data[i].rx - GraphBandwidthData[i].rx_old)));
            GraphBandwidthData[i].rx.push([Now,rx_rate]);
        } else  GraphBandwidthData[i].rx.push([Now,0]);
        // Reset the old and new values for the next iteration.
        GraphBandwidthData[i].dev = data[i].dev;
        GraphBandwidthData[i].tx_old = data[i].tx;
        GraphBandwidthData[i].rx_old = data[i].rx;
    }
    GraphData = new Array();
    for (i in GraphBandwidthData) GraphData.push({label: i+' ('+GraphBandwidthData[i].dev+')', data: (GraphBandwidthFilterTransmitActive ? GraphBandwidthData[i].tx : GraphBandwidthData[i].rx)});
    $.plot(GraphBandwidth,GraphData,GraphBandwidthOpts);
}
// Client Count Functions.
function UpdateClientCount() {
    GroupID = ClientCountGroup.val();
    if (GroupID === null || typeof(GroupID) == 'undefined' || GroupID.length === 0) return;
    $.ajax({
        url: '?node=home',
        type: 'POST',
        data: {
            sub: 'clientcount',
            id: GroupID
        },
        dataType: 'json',
        success: function(data) {
            if (data === null || typeof(data) == 'undefined' || data.length == 0) return;
            UpdateClientCountPlot(data);
        },
        complete: function() {setTimeout(UpdateClientCount,10000);}
    });
}
function UpdateClientCountPlot(data) {
    if (data === null || typeof(data) == 'undefined' || data.length == 0) return;
    UpdateClientCountData = [
        {label:'Active',data:parseInt(data.ActivityActive)},
        {label:'Queued',data:parseInt(data.ActivityQueued)},
        {label:'Free',data:parseInt(data.ActivitySlots)}
    ];
    $.plot(GraphClient,UpdateClientCountData,UpdateClientCountOpts);
}
