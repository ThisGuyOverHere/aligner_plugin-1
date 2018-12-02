import React from 'react';

const AnalyseError = () => (
   <div id={"job-error"}>
       <img className={"logo"} src={"/public/img/logoblack.png"}/>
       <div className={"title"}>
           <h1> Sorry, the analysing phase failed. </h1>
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