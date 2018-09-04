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
                <div id="title">
                    <h1>Matecat's intelligence is currently aligning your files, please wait</h1>
                </div>
                <PreAlignStatus props = {this.props}/>
                <Animation/>
            </div>
        );
    }
}

export default AnalyseComponent;