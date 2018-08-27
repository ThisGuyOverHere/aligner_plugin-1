import React, {Component} from 'react';

class FooterComponent extends Component {
    render() {
        return (
            <div id="footer">
                <ul className="inline-list align-middle">
                    <li className="list-inline__item">
                        <div className='ui progress' data-percent="50">
                            <div className='bar' style={{width:'50%'}}/>
                        </div>
                        50%
                    </li>
                    <li className="list-inline__item">
                        <p>Lines: <b>8</b></p>
                    </li>
                    <li className="list-inline__item"><p>Empty spaces Source: <b>3</b> / Target: <b>2</b></p></li>
                </ul>
            </div>
        );
    }
}

export default FooterComponent;