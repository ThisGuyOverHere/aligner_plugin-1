import React from 'react';

const JobError = () => (
   <div id={"job-error"}>
       <img className={"logo"} src={"/public/img/logoblack.png"}/>
       <div className={"title"}>
           <h1> Sorry, we can't find the project you're looking for.  </h1>
       </div>
       <img className={"image-not-found"} src={"/public/img/jobnotfound.png"}/>
       <div className="suggestions">
           <p>
               Double check the URL or contact your PM. <br/>
               If you continue to get this page, email us at <a href={"mailto:support@matecat.com"}>support@matecat.com.</a>
           </p>
       </div>
   </div>
);

export default JobError;