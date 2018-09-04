import React, {Component} from 'react';

class Mismatch extends Component {

    static propTypes = {

    };

    constructor(props) {
        super(props);
        this.state = {

        };
    }

    render() {
        return (
            <div id="mini_align_nav">
                <p id="aligned"> Mismatch 1 </p> / 5
                <span>
                    <i className="icon angle up"></i>
                </span>
                <span>
                    <i className="icon angle down"></i>
                </span>
            </div>
        );
    }
}
export default Mismatch;