import React from 'react';

const JobError = () => (
   <div className={"job-error"}>
       <div className={"text"}>
           <h1> Error  </h1>
           <h4> Invalid alignment link. </h4>
       </div>
       <a href={"/plugins/aligner/index"} className={"new-align"}> New Align </a>
   </div>
);

export default JobError;