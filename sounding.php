<!DOCTYPE html>
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="sounding.css">
<title></title>
<body>

<?php
for ($i=0; $i<=48; $i++) {
    echo "<div id=\"$i\" class=\"rollover\">".sprintf("%02d", $i)."</div>";
}	
?>
<div id="mainbox"></div>

<script src="d3.v3.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script src="jsfunctions.js"></script>
<script type="text/javascript">

$(document).ready(function() {
    $(" body ").data( "currsnd", 0);
    $(" div.rollover " ).mouseover(function() {
        var i = $( this ).attr('id');
        data = $(" body ").data();
        $('.rollover'+data.currsnd).hide();
        $('.rollover'+i).show();
        $(" body ").data( "currsnd", i);
    });
});

var m = [30, 50, 20, 35],
    w = 700 - m[1] - m[3],
    h = 700 - m[0] - m[2];
    
var tan = Math.tan(55*(Math.PI/180)),
    basep = 1050,
    topp = 100,
    plines = [1000,850,700,500,300,200,100]
    pticks = [950,900,800,750,650,600,550,450,400,350,250,150];

// Scales and axes. Note the inverted domain for the y-scale: bigger is up!
var x = d3.scale.linear().range([0, w]).domain([-42,50]),
    y = d3.scale.log().range([0, h]).domain([topp, basep])
    y2 = d3.scale.linear(),
    xAxis = d3.svg.axis().scale(x).tickSize(0,0).ticks(10).orient("bottom"),
    yAxis = d3.svg.axis().scale(y).tickSize(0,0).tickValues(plines)
              .tickFormat(d3.format(".0d")).orient("left")
    yAxis2 = d3.svg.axis().scale(y).tickSize(5,0).tickValues(pticks).orient("right"); // just for ticks
    //yAxis2 = d3.svg.axis().scale(y2).orient("right").tickSize(3,0).tickFormat(d3.format(".0d"));

var bisectTemp = d3.bisector(function(d) { return -d.press; }).left;

var line = d3.svg.line()
    .interpolate("linear")
    .x(function(d,i) { return x(d.temp) + (y(basep)-y(d.press))/tan; })
    //.x(function(d,i) { return x(d.temp); })
    .y(function(d,i) { return y(d.press); });
    
var line2 = d3.svg.line()
    .interpolate("linear")
    .x(function(d,i) { return x(d.dwpt) + (y(basep)-y(d.press))/tan; })
    .y(function(d,i) { return y(d.press); });
    
var hodoline = d3.svg.line.radial()
    .radius(function(d) { return d.wspd; })
    .angle(function(d) { return (d.wdir+180)*(Math.PI/180); });

// create svg container
var svg = d3.select("div#mainbox").append("svg")
      .attr("width", w + m[1] + m[3])
      .attr("height", h + m[0] + m[2])
    .append("g")
      .attr("transform", "translate(" + m[3] + "," + m[0] + ")");

drawBackground(svg);

  d3.xhr('sounding.txt').get(function (err, response) {

  for (k=0; k<=48; k++) {
    var dirtyCSV = response.responseText;
    var cleanCSV = dirtyCSV.split('\n').join('\n');
    var parsedCSV = d3.csv.parse(cleanCSV);

    parsedCSV.forEach(function(d) {
				d.press = +d.press;
				d.temp = +d.temp;
				d.dwpt = +d.dwpt;
				d.hght = +d.hght;
				d.wdir = +d.wdir;
				d.wspd = +d.wspd;
				
				var rounded = Math.round(d.wspd/5) * 5;
				d.flags        = Math.floor(rounded/50);
				d.pennants     = Math.floor((rounded - d.flags*50)/10);
				d.halfpennants = Math.floor((rounded - d.flags*50 - d.pennants*10)/5 );
				d.barbsum = d.flags + d.pennants + d.halfpennants;
	});
  
    // Set y-axis parameters based on data
    //domaintop = parsedCSV.filter(function(d) { return (d.press == topp); });
    //y2.range([0, y(parsedCSV[1].press)])
    //  .domain([domaintop[0].hght/1000 - parsedCSV[0].hght/1000, 0]);
    //yAxis2.tickValues([1,3,6,9,12,15]);
     
	parsedCSV = parsedCSV.filter(function(d) { return (d.temp > -1000 && d.dwpt > -1000 ); }); // remove below ground data
	barbs = parsedCSV.filter(function(d) { return (d.wdir >= 0 && d.wspd >= 0 && d.press >= topp); });
	hodobarbs = barbs.filter(function(d) { return (d.press>=175); });
    
    // interpolate to given heights
    var requestedLevels = [0,1,3,6,9,12,15]; // levels in km agl
    var test = requestedLevels.map(function(d) {
    	if (d == 0) { return parsedCSV[0]; }
    	
    	d = 1000*d+parsedCSV[0].hght; // want height AGL
        for (i=0; i<=parsedCSV.length; i++) {
            if (parsedCSV[i].hght > d) { var closeindex = i; break; } // since hghts increase monotonically
        }
        var interp = d3.interpolateObject(parsedCSV[i-1],parsedCSV[i]); // interp btw two levels
        var half = interp(1-(d - parsedCSV[i].hght)/(parsedCSV[i-1].hght - parsedCSV[i].hght));
        return half
    });
    test = test.filter(function(d) { return (d.press >= topp) }); // dont plot above top of domain
    
    // height labels on left axis
    //svg.selectAll("texthght")
    //    .data(test)
    //  .enter().append("text")
    //    .attr("class", "y axis hght")
    //    .attr("text-anchor", "left")
    //    .attr("x", 0)
    //    .attr("y", function(d) { return y(d.press); })
    //    .attr("dy", 2)
    //    .text(function(d,i) { 
    //             if (i==0) { return "-- SFC ("+parsedCSV[0].hght+"m)"; }
    //             else {	return "-- "+requestedLevels[i]+" km"; }
    //             });
    
    // Draw temperature
	svg.append("path")
        //.attr("transform", "translate(0," + h + ") skewX(-30) translate(0,-" + h + ")")
          .attr("class", "temp sounding rollover"+k)
          .attr("clip-path", "url(#clipper)")
          .attr("d", line(parsedCSV));
          
    // Draw dew point temperature
    svg.append("path")
          //.attr("transform", "translate(0," + h + ") skewX(-30) translate(0,-" + h + ")")
          .attr("class", "dwpt sounding rollover"+k)
          .attr("clip-path", "url(#clipper)")
          .attr("d", line2(parsedCSV));
    
    // Draw hodoline
    svg.append("path").attr("class", "hodoline").attr("d", hodoline(hodobarbs)).attr("transform", "translate(450,100)");	

	// Draw wind barb stems
  	var barbsize = 25;
  	svg.selectAll("barbs")
    	.data(barbs)
   	.enter().append("line")
     	.attr("x1", 0)
     	.attr("x2", 0)
     	.attr("y1", 0)
     	.attr("y2", barbsize)
     	.attr("transform", function(d,i) { return "translate("+w+","+y(d.press)+") rotate("+(d.wdir+180)+")"; })
     	.attr("class", "barb");
     
    // Draw wind barb flags and pennants for each stem
	barbs.forEach(function(d) {
	    var px = barbsize;
	    // Draw flags on each barb
	    for (i=0; i<d.flags; i++) {
     		 svg.append("polyline")
     		 	.attr("points", "0,"+px+" -10,"+(px)+" 0,"+(px-4))
     		 	.attr("transform", "translate("+w+","+y(d.press)+") rotate("+(d.wdir+180)+")")
     		    .attr("class", "flag");
     		 px -= 7;
     		}
     		
	    // Draw pennants on each barb
	    for (i=0; i<d.pennants; i++) {
    	    svg.append("line")
     		    .attr("x1", 0)
     		    .attr("x2", -10)
     		    .attr("y1", px)
     		    .attr("y2", px+4)
     		    .attr("transform", "translate("+w+","+y(d.press)+") rotate("+(d.wdir+180)+")")
     		    .attr("class", "barb");
     		 px -= 3;
     		}
     		
     	// Draw half-pennants on each barb
        for (i=0; i<d.halfpennants; i++) {
    	    svg.append("line")
     		    .attr("x1", 0)
     		    .attr("x2", -5)
     		    .attr("y1", px)
     		    .attr("y2", px+2)
     		    .attr("transform", "translate("+w+","+y(d.press)+") rotate("+(d.wdir+180)+")")
     		    .attr("class", "barb");
     		px -= 3;
     		} 	
    });
    
  // Draw T/Td tooltips
  var focus = svg.append("g")
      .attr("class", "focus")
      .style("display", "none");
  focus.append("circle")
      .attr("r", 3);
  focus.append("text")
      .attr("x", 9)
      .attr("dy", ".35em");
      
  var focus2 = svg.append("g")
      .attr("class", "focus2")
      .style("display", "none");
  focus2.append("circle")
      .attr("r", 3);
  focus2.append("text")
      .attr("x", -9)
      .attr("text-anchor", "end")
      .attr("dy", ".35em");

  svg.append("rect")
      .attr("class", "overlay")
      .attr("width", w)
      .attr("height", h)
      .on("mouseover", function() { focus.style("display", null); focus2.style("display", null);})
      .on("mouseout", function() { focus.style("display", "none"); focus2.style("display", "none");})
      .on("mousemove", mousemove);

  function mousemove() {
      var y0 = y.invert(d3.mouse(this)[1]); // get y value of mouse pointer
      //console.log(y0);
	  var i = bisectTemp(parsedCSV, -y0, 1); // negative y0 to get sorted values?
	  //console.log(parsedCSV.length);
	  //console.log(i);
      var d0 = parsedCSV[i - 1];
      var d1 = parsedCSV[i];
      var d = y0 - d0.temp > d1.temp - y0 ? d1 : d0;
      focus.attr("transform", "translate(" + (x(d.temp) + (y(basep)-y(d.press))/tan)+ "," + y(d.press) + ")");
      focus2.attr("transform", "translate(" + (x(d.dwpt) + (y(basep)-y(d.press))/tan)+ "," + y(d.press) + ")");
      focus.select("text").text(Math.round(d.temp)+"°C");
      focus2.select("text").text(Math.round(d.dwpt)+"°C");
  }
     
  } //end 48 for loop
          
  });
       
</script>

</body>
</html>
