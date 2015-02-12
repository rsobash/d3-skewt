
function drawBackground() {

var svghodo = d3.select("div#hodobox svg g").append("g").attr("class", "hodobg");
var svg = d3.select("div#mainbox svg g").append("g").attr("class", "skewtbg");

var dryline = d3.svg.line()
    .interpolate("linear")
    .x(function(d,i) { return x( ( 273.15 + d ) / Math.pow( (1000/pp[i]), 0.286) -273.15) + (y(basep)-y(pp[i]))/tan;})
    .y(function(d,i) { return y(pp[i])} );

// Add clipping path
  svg.append("clipPath")
    .attr("id", "clipper")
    .append("rect")
    .attr("x", 0)
    .attr("y", 0)
    .attr("width", w)
    .attr("height", h);
    
// Skewed temperature lines
  svg.selectAll("gline")
    .data(d3.range(-100,45,10))
   .enter().append("line")
     .attr("x1", function(d) { return x(d)-0.5 + (y(basep)-y(100))/tan; })
     //.attr("x1", function(d) { return x(d)-0.5; })
     .attr("x2", function(d) { return x(d)-0.5; })
     .attr("y1", 0)
     .attr("y2", h)
     .attr("class", function(d) { if (d == 0) { return "tempzero"; } else { return "gridline"}})
     .attr("clip-path", "url(#clipper)");
     //.attr("transform", "translate(0," + h + ") skewX(-30)");
     
// Logarithmic pressure lines
 	svg.selectAll("gline2")
    	.data(plines)
   	.enter().append("line")
     	.attr("x1", 0)
     	.attr("x2", w)
     	.attr("y1", function(d) { return y(d); })
     	.attr("y2", function(d) { return y(d); })
     	.attr("class", "gridline");
     
// create array to plot dry adiabats
var pp = d3.range(topp,basep+1,10);
var dryad = d3.range(-30,240,20);
var all = [];
for (i=0; i<dryad.length; i++) { 
    var z = [];
    for (j=0; j<pp.length; j++) { z.push(dryad[i]); }
    all.push(z);
}

// Draw dry adiabats
svg.selectAll(".dryline")
    .data(all)
.enter().append("path")
    .attr("class", "gridline")
    .attr("clip-path", "url(#clipper)")
    .attr("d", dryline);
    
// Line along right edge of plot
  svg.append("line")
     .attr("x1", w-0.5)
     .attr("x2", w-0.5)
     .attr("y1", 0)
     .attr("y2", h)
      .style("stroke", "#aaa")
     .attr("stroke-width", "0.75px");
    
    // draw hodograph background
   svghodo.selectAll(".circles")
       .data(d3.range(10,100,10))
    .enter().append("circle")
       .attr("cx", 150)
       .attr("cy", 150)
       .attr("r", function(d) { return r(d); })
       .attr("class", "gridline");
       
       	// Add axes
    svg.append("g").attr("class", "x axis").attr("transform", "translate(0," + (h-0.5) + ")").call(xAxis);
    svg.append("g").attr("class", "y axis").attr("transform", "translate(0,0)").call(yAxis);
    svg.append("g").attr("class", "y axis ticks").attr("transform", "translate(0,0)").call(yAxis2);
    //svg.append("g").attr("class", "y axis hght").attr("transform", "translate(0,0)").call(yAxis2);
}
