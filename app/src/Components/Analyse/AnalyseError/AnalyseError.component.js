import React from 'react';

const AnalyseError = (props) => (
   <div id={"job-error"}>
       <img className={"logo"} src={"/public/img/logoblack.png"}/>
       <div className={"title"}>
           <h2> {props.message} </h2>
       </div>
       <img className={"image-not-found"} src={"/public/img/jobnotfound.png"}/>
       <div className="suggestions">
           <p>
               If you continue to get this page, email us at <a href={"mailto:support@matecat.com"}>support@matecat.com.</a>
           </p>
       </div>
   </div>
);

export default AnalyseError;