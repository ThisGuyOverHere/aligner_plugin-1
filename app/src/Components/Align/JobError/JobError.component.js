import React from 'react';

const JobError = (props) => (
   <div className={"job-error"}>
       <div className={"text"}>
           <h1> Error message </h1>
           <h4> Short description of error </h4>
       </div>
       <button className={"new-align"}> New Align </button>
   </div>
);

export default JobError;