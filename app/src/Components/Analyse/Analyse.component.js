import React, {Component} from 'react';
import PreAlignStatus from "./PreAlignStatus/PreAlignStatus.component";
import Animation from "./Animation/Animation.component";


class AnalyseComponent extends Component {
    static propTypes = {};

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div>
                <h1>Matecat's intelligence is currently alligning your files, please wait</h1>
                <PreAlignStatus props = {this.props}/>
                <Animation/>
            </div>
        );
    }
}

export default AnalyseComponent;