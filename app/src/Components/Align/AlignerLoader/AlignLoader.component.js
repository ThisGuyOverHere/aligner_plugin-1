import React from 'react';

const AlignLoader = () => (
        <div id='element-wrapper'>
            {[0,1,2].map( (index) => {
                return   <div key={index} className='element'/>
            })}
            <div className='align-loading'>Loading…</div>
            <div className='large-square'/>
        </div>
);

export default AlignLoader;